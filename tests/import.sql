
DROP TABLE IF EXISTS test_import;

CREATE TABLE test_import (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    username VARCHAR(50) NOT NULL, 
    amount DECIMAL(6,2) NOT NULL
);

INSERT INTO test_import (username, amount) VALUES ('jsmith', 50), ('mike', 15.51), ('loren', 22.59);
INSERT INTO test_import VALUES (0, 'brad', 8155);



