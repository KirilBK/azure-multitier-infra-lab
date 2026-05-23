CREATE TABLE voteoptions (
 optionname VARCHAR(25), 
 PRIMARY KEY (optionname)); 
 
GO

INSERT INTO voteoptions (optionname) VALUES ('Cats');
INSERT INTO voteoptions (optionname) VALUES ('Dogs');

GO

CREATE TABLE votecasts (
 id INT IDENTITY(1,1),
 voteoption VARCHAR(25), 
 PRIMARY KEY (id));

GO 

INSERT INTO votecasts (voteoption) VALUES ('Cats');
INSERT INTO votecasts (voteoption) VALUES ('Dogs');

GO