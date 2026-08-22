-- Stage 3 Cutover: Gallery/Facilities/Media native-v3 + Audit primary + PPDB legacy mirror off
-- Jalankan setelah 03_professional_upgrade_ppdb.sql / php artisan migrate v3.

ALTER TABLE `media_assets`
  ADD COLUMN IF NOT EXISTS `collection` VARCHAR(60) NULL AFTER `file_path`,
  ADD COLUMN IF NOT EXISTS `is_public` TINYINT(1) NOT NULL DEFAULT 1 AFTER `alt_text`;

CREATE TABLE IF NOT EXISTS `gallery_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `legacy_gallery_id` INT UNSIGNED NULL,
  `title` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `image_asset_id` BIGINT UNSIGNED NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `published_at` TIMESTAMP NULL,
  `created_by_account_id` BIGINT UNSIGNED NULL,
  `updated_by_account_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gallery_items_legacy_gallery_id_unique` (`legacy_gallery_id`),
  KEY `gallery_items_published_idx` (`is_published`, `published_at`),
  KEY `gallery_items_sort_order_idx` (`sort_order`),
  KEY `gallery_items_image_asset_id_foreign` (`image_asset_id`),
  CONSTRAINT `gallery_items_image_asset_id_foreign` FOREIGN KEY (`image_asset_id`) REFERENCES `media_assets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `facilities`
  ADD COLUMN IF NOT EXISTS `published_at` TIMESTAMP NULL AFTER `is_published`;

UPDATE `facilities`
SET `published_at` = COALESCE(`created_at`, NOW())
WHERE `is_published` = 1 AND `published_at` IS NULL;

-- Backfill media untuk gallery legacy
INSERT IGNORE INTO `media_assets` (`disk`, `file_path`, `collection`, `original_name`, `alt_text`, `is_public`, `created_at`, `updated_at`)
SELECT 'public', `image_path`, 'gallery', SUBSTRING_INDEX(`image_path`, '/', -1), `title`, 1, COALESCE(`uploaded_at`, NOW()), NOW()
FROM `gallery`
WHERE `image_path` IS NOT NULL AND `image_path` <> '';

INSERT INTO `gallery_items` (`legacy_gallery_id`, `title`, `description`, `image_asset_id`, `is_published`, `sort_order`, `published_at`, `created_at`, `updated_at`)
SELECT g.`id`, g.`title`, g.`description`, m.`id`, COALESCE(g.`is_published`, 1), 0,
       CASE WHEN COALESCE(g.`is_published`, 1) = 1 THEN COALESCE(g.`uploaded_at`, NOW()) ELSE NULL END,
       COALESCE(g.`uploaded_at`, NOW()), NOW()
FROM `gallery` g
LEFT JOIN `media_assets` m ON m.`file_path` = g.`image_path`
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `image_asset_id` = VALUES(`image_asset_id`),
  `is_published` = VALUES(`is_published`),
  `published_at` = VALUES(`published_at`),
  `updated_at` = NOW();

-- Backfill media fasilitas
INSERT IGNORE INTO `media_assets` (`disk`, `file_path`, `collection`, `original_name`, `alt_text`, `is_public`, `created_at`, `updated_at`)
SELECT 'public', `modal_image_path`, 'facilities', SUBSTRING_INDEX(`modal_image_path`, '/', -1), `name`, 1, COALESCE(`created_at`, NOW()), NOW()
FROM `facilities`
WHERE `modal_image_path` IS NOT NULL AND `modal_image_path` <> '';

UPDATE `facilities` f
JOIN `media_assets` m ON m.`file_path` = f.`modal_image_path`
SET f.`image_asset_id` = COALESCE(f.`image_asset_id`, m.`id`),
    f.`published_at` = CASE WHEN f.`is_published` = 1 THEN COALESCE(f.`published_at`, f.`created_at`, NOW()) ELSE NULL END;
