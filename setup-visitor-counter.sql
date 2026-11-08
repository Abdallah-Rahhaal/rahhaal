CREATE TABLE IF NOT EXISTS rahhal_visitors (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visitor_hash CHAR(64) NOT NULL,
  first_seen DATETIME NOT NULL,
  last_seen DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_visitor_hash (visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
