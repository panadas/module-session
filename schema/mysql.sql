CREATE TABLE `session` (
    `id` VARCHAR(27) COLLATE utf8_unicode_ci NOT NULL,
    `data` LONGTEXT COLLATE utf8_unicode_ci NOT NULL,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_modified` (`modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
