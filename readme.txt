=== Backup Bolt ===
Contributors: backupbolt
Tags: backup, wordpress backup, download backup, backups,restore, cloud backup, migrate
Requires at least: 4.0
License: GPL3
Tested up to: 6.6
Requires PHP: 5.6
Stable tag: 1.4.0

Super simple one click backup your site and download the backup in compressed zip format. Choose between custom or full WordPress backup. Super easy interface without any clutter.

== Description ==

Backup Bolt is the easiest and simplest one click backup plugin you could ever find!. Now take a custom backup with minimal storage consumption or backup your full WordPress site with a click of button!. Clutter free straight forward interface. Download backup with zip compression.

== BACKUP FEATURES ==
* Super easy backup interface
* Custom backup for minimal storage consumption
* Full WordPress backup
* Backup size v/s Free memory calculation
* PCLZIP based faster backup process
* Realtime process logging during backup
* Generate large backups within seconds
* Download the backup in zip format
* Backups are auto deleted daily for storage consumption

= Show Your Support =

* Rate Plugin – If you find this plugin useful, please leave a [positive review](https://wordpress.org/support/plugin/backup-bolt/reviews/). Your reviews are our biggest motivation for further development of the plugin.
* Submit a Bug – If you find any issue, please submit a bug via support forum.

Thanks to [SweetAlert2](https://sweetalert2.github.io/) for beautiful alert box script.

== Installation ==	
1. Download the plugin
2. Upload the plugin to the wp-content/plugins directory,
3. Go to “plugins” in your WordPress admin, then click activate.
4. You will now see Backup Bolt option on your left navigation bar. Click on it and start taking backups.

== Frequently Asked Questions ==

= How fast is the backup process? =
Backup Bolt initiates backup via PHP PclZip which is the most powerful and fastest solution possible compared to any other backup methods. Usually backups are completed within seconds!.

= Does backups consume large memory? =
You can choose "Custom" backup option to backup only the important wp-content part of your WordPress site so the rest can be downloaded directly via wp.org when you are restoring the backup. At the end, the backups are zip compressed for lowest memory consumption.

= How long are the backups retained on server? =
Backup Bolt is focused towards storage optimization so backups are auto deleted after 24 hours to avoid flooding server storage. We recommend downloading the backup to your local computer immediately after running the backup.

= Where are the backup files stored? =
Backups are stored in a secure dynamic folder located in wp-content/backup-bolt-*

= Does repeated backups increase backup size? =
Backups created by Backup Bolt are always excluded in further backup process so the backup size remains same even if you backup multiple times.

== Screenshots ==
1. Backup Bolt interface
2. Backup size and free memory size calculation before initiating backup process
3. Download the backup instantly in zip format

== Changelog ==

= 1.4.0 =
* error logging removed
* FS sdk updated
* tested upto tag update

= 1.3.0 =
* FS sdk fix

= 1.1.3 =
* Fixed deactivation issue when backup folder already deleted / don't exist

= 1.1.2 =
* termninate on fatal errors only

= 1.1.0 =
FS sdk integration
log improvements

= 1.0.0 =
Initial commit