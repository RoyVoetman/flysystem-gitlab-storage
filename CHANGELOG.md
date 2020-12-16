## Changelog

All notable changes to `flysystem-gitlab-storage` will be documented in this file

### 0.0.1 - 2019-08-02
- initial commit.

### 1.0.0 - 2019-08-03
- Implemented createDir by creating a directory with a .gitkeep file.
- Small bug fixes.

### 1.0.1 - 2019-08-03
- Updated CHANGELOG.

### 1.0.2 - 2019-08-03
- Updated composer.json to support laravel projects.

### 1.0.3 - 2019-08-03
- Fixed packagist versioning issue.

### 1.0.4 - 2019-08-03
- Added support for tree path with multiple sub folders.

### 1.0.5 - 2019-08-03
- Adapters read method now returns an array instead of raw content.

### 1.0.6 - 2019-08-03
- Adapters listContents method now changes type blob to type file.

### 1.0.7 - 2020-03-20
- Added a debug mode.

### 1.1.0 - 2020-06-29
- Moved minimum PHP version to 7.1 since PHPUnit 9 requires 7.1 or above.
- Added support for paginated list of contents when requesting file trees.
- [https://docs.gitlab.com/ee/api/README.html#pagination](https://docs.gitlab.com/ee/api/README.html#pagination)

### 2.0.0 - 2020-11-30
- Migrated to flysystem 2.x

### 2.0.1 - 2020-12-01
- Added php 8 support

### 2.0.2 - 2020-12-01
- Allow to read into stream

### 2.0.3 - 2020-12-16
-  Reuse stream of HTTP request instead of create new stream

### 2.0.4 - 2020-12-16
- Savings HTTP exchanges with HEAD request
