



##DB: MUKategorys
CREATE TABLE MUKategorys(
  ID int NOT NULL auto_increment PRIMARY KEY,

  Name varchar(20) NOT NULL, # Name der Kategory
  projectID int NOT NULL, # Projekt-ID FK usermanagement@localhost.UserManagement.MUProject.ID
  parentID int, # FK MUKategorys.ID
  new enum('N','Y') NOT NULL, # ob ein neuer Beitrag von jedem hinzugef�gt werden darf
  app enum('N','Y') NOT NULL, # ob zu jedem vorhandenen Beitrag weitere Fragen hinzugef�gt werden darf
  del enum('N','Y') NOT NULL, # ob Beitr�ge vom Verantwortlichen gel�scht werden d�rfen
  up  enum('N','Y') NOT NULL, # ob beim Beitrag ein Attachment angeh�ngt werden darf
  info varchar(50) # FK UserManagement.ID bei NULL wird die Email an den Verantwortlichen geschickt, sonst an alle vom entsprechendem Cluster
);