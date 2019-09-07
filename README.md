# MWUpdateKit
A kit that used to help people to update their wiki plowed by MediaWiki.

## Usage
The kit only one CLI entry, `runKit.php`.
```bash
php runKit.php
```
The kit contains a lot of commands, you can use according to your needs.
### Check system environment
Check if your system environment can install/update MediaWiki.
```bash
php runKit.php prepare:envCheck
```
Note: This check does not guarantee that the MediaWiki installation will succeed.
### Prepare extension & skin
This command can download new extensions based on existing extensions.
```bash
php runKit.php prepare:ext
```
Note: This feature cannot prepare extensions that are not hosted in WMF-gerrit

## Support
If this kit on the way in the use of the problem or you have any ideas,
please go to the [Github issues](https://github.com/RazeSoldier/MWUpdateKit/issues).