SQL :

```sql
CREATE TABLE `earth_sandwich_telegram_bot`.`messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `activity_id` VARCHAR(255) NOT NULL,
  `message` LONGTEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB;
```
