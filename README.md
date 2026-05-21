# Azure Multi-Tier Web Infrastructure Lab

A hands-on Azure cloud infrastructure project deploying a fully functional multi-tier web application using virtual machines, load balancing, containerization, PaaS services, and security best practices.

![Architecture](RG-AzureInfraLab.png)

---

## Architecture Overview

This project provisions and configures the following Azure resources across a segmented virtual network with security-first design:

- **2x Ubuntu 24.04 VMs** in an Availability Set behind an external Standard Load Balancer
- **Azure Container Registry (ACR)** with a Dockerized PHP application
- **Azure Container Instance** running the containerized app
- **Azure SQL Server + Database** accessed via Private Endpoint (no public NIC exposure)
- **Azure Key Vault** storing the SQL connection string as a secret
- **Private DNS Zone** resolving the SQL private endpoint internally
- **2x Azure App Service** (PHP 8.5) on a Linux App Service Plan
- **Virtual Network** with two subnets — one for VMs, one for the database tier

---

## Infrastructure Components

### Compute & Availability
| Resource | Details |
|---|---|
| VM-1, VM-2 | Ubuntu 24.04 LTS, Standard B2ats_v2, Switzerland North |
| Availability Set | AS-VM, 2 fault domains, 5 update domains, Aligned SKU |
| Container Instance | kirilbk-aci, PHP voting app |

### Networking
| Resource | Details |
|---|---|
| Virtual Network | NET — 10.0.0.0/16 |
| Subnet (VMs) | NET-SUB-VM — 10.0.1.0/24 |
| Subnet (DB) | NET-SUB-DB — 10.0.2.0/24 |
| NSG | SG-VM — subnet-level only, inbound SSH (22) and HTTP (80) |
| Load Balancer | LBP — Standard, External, Switzerland North |
| LB Frontend | LBP-FE with public IP LBP-IP |
| LB Backend Pool | LBP-BEP — VM-1 and VM-2 |
| LB Health Probe | LBP-HP — HTTP port 80 |
| LB Rule | LBP-RULE — port 80 → 80 |
| LB Outbound Rule | LBP-OUT — 128 allocated ports per instance |
| NAT Rules | LBP-NAT-SSH-1 (50001 → VM-1:22), LBP-NAT-SSH-2 (50002 → VM-2:22) |

### Security
| Resource | Details |
|---|---|
| Key Vault | kirilbkvault — Standard tier, network-restricted |
| Secret | SqlConnectionString — full ADO.NET connection string |
| Private Endpoint | sql-private-endpoint — SQL Server via NET-SUB-DB |
| Private DNS Zone | privatelink.database.windows.net |

### Database
| Resource | Details |
|---|---|
| SQL Server | kirilbkproject — Switzerland North |
| SQL Database | kirilbkdatabase — Basic tier (5 DTU) |
| Access | Private endpoint + client IP firewall rule + Azure services |

### PaaS
| Resource | Details |
|---|---|
| App Service Plan | ASP-LINUX — Linux, F1 Free |
| App Service (app2) | kirilbkapp2 — PHP 8.5, no DB |
| App Service (app3) | kirilbkapp3 — PHP 8.5, connected to SQL |
| Container Registry | kirilbkcontreg — Standard SKU, Admin enabled |

---

## Application Tiers

### app1 — VM-based PHP App (Load Balanced)
- Deployed to both VM-1 and VM-2
- Apache + PHP with Microsoft ODBC Driver 18 for SQL Server
- Connects to Azure SQL via private endpoint
- Accessible via load balancer public IP

### app2 — Azure App Service (No DB)
- Stateless PHP voting UI
- Deployed via ZIP deploy to Linux App Service

### app3 — Azure App Service (DB Connected)
- PHP voting app with results from Azure SQL
- Deployed via ZIP deploy to Linux App Service

### app4 — Containerized PHP App
- Built from Dockerfile, pushed to ACR
- Deployed as Azure Container Instance
- Connects to Azure SQL Database
- Publicly accessible via container public IP

---

## Key Technical Steps

### VM Setup (both VMs)
```bash
# Install Apache + PHP
sudo apt update && sudo apt install -y apache2 php libapache2-mod-php php-dev

# Add Microsoft package repository
curl -sSL https://packages.microsoft.com/keys/microsoft.asc | sudo gpg --dearmor -o /usr/share/keyrings/microsoft.gpg
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/ubuntu/24.04/prod noble main" | sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt update
sudo ACCEPT_EULA=Y apt install -y msodbcsql18 unixodbc-dev

# Install PHP SQL Server extensions
sudo pecl install sqlsrv pdo_sqlsrv
echo "extension=sqlsrv.so" | sudo tee -a /etc/php/8.3/apache2/php.ini
sudo systemctl restart apache2

# Verify
php -m | grep -i sql
```

### Docker Build & Push to ACR
```bash
docker build -t app4-image .
az acr login --name <acr-name>
docker tag app4-image <acr-name>.azurecr.io/app4-image:v1
docker push <acr-name>.azurecr.io/app4-image:v1
```

### App Service Deployment
```powershell
Compress-Archive -Path ".\app2\*" -DestinationPath ".\app2.zip"
az webapp deploy --resource-group RG-AzureInfraLab --name <app-name> --src-path ".\app2.zip" --type zip
```

### PHP SQL Server Connection (config.php)
```php
<?php
    $server = "tcp:<server>.database.windows.net,1433";
    $database = "<database>";
    $username = "<username>";
    $password = '<password>';

    $conn = sqlsrv_connect($server, array(
        "Database" => $database,
        "UID" => $username,
        "PWD" => $password,
        "Encrypt" => true,
        "TrustServerCertificate" => false
    ));
?>
```

---

## Security Design Decisions

- **Single NSG at subnet level** — no per-VM NSGs, avoids rule conflicts and reduces attack surface
- **No public IPs on VMs** — all VM access routed through Load Balancer NAT rules
- **SQL Private Endpoint** — SQL Server NIC sits in NET-SUB-DB, not exposed to public internet
- **Private DNS Zone** — internal name resolution for private endpoint without manual host file entries
- **Key Vault** — connection strings stored as secrets, not hardcoded in application config files
- **Key Vault network restriction** — access limited to specific subnets via service endpoints

---

## Lessons Learned

- **Outbound rule must be created upfront** with allocated ports > 0 (used 128) — Azure won't save it with 0
- **ZIP deploy creates a subfolder** — always SSH into App Service and run `cp -r /home/site/wwwroot/app*/* /home/site/wwwroot/`
- **ACR names must be all lowercase** in Docker commands regardless of portal display name
- **Docker Desktop engine hangs** require a full PC restart — not just Docker restart
- **Key Vault RBAC** requires explicit `Key Vault Secrets Officer` role assignment before secrets can be created
- **PHP sqlsrv extension** is not included in Azure App Service Linux images by default

---

## Resources

- [Azure Load Balancer documentation](https://docs.microsoft.com/en-us/azure/load-balancer/)
- [Azure Private Endpoint](https://docs.microsoft.com/en-us/azure/private-link/private-endpoint-overview)
- [Azure Key Vault](https://docs.microsoft.com/en-us/azure/key-vault/)
- [Microsoft ODBC Driver for SQL Server on Linux](https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server)
- [Azure Container Instances](https://docs.microsoft.com/en-us/azure/container-instances/)
- [Azure App Service PHP deployment](https://docs.microsoft.com/en-us/azure/app-service/quickstart-php)
