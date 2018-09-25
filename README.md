# PhpStorm Reset Trial Period

**ATTENTION: PhpStorm is a great IDE. Its authors deserve a reward. You should buy licence, seriously. You need a very serious excuse to use this utility. Ideally, you need to have a purchased license, which you do not like to use because of the anti-piracy mechanism that requires a persistent connection to the Internet.**

This repo contains the utility which allow you to reset trial period in your PhpStorm installation (even if it's already expired) in a very easy and convenient way without losing your individual PhpStorm preferences. The easiest way to reset trial period manually is to delete PhpStorm's config folder usually located at `{user.home}/.PhpStorm/config` (but you can relocate it to any other folder by editing `PhpStorm 2018\bin\idea.properties`). Windows users additionally need to remove registry key `HKEY_CURRENT_USER\SOFTWARE\JavaSoft\Prefs\jetbrains\phpstorm`. After this if you will start PhpStorm it will think that it was just installed first time and will offer you to select evaluation 30 days period.

Disadvantage of this method is losing all you preferences: you hot-keys, appearance, editor settings, last projects, etc. This utility is backing up you config folder, cleans registry (on Windows) and after you started PhpStorm and selected new evaluation period merges new config with old one.

Actually, when merging configs we need to copy all files except `eval/*` and `options/options.xml` from backup to actual config directory. The `options/options.xml` file need to be merged more intellectually line by line with adding `<property>` XML nodes from backed up `options/options.xml` except `evlsprt*` properties.

## Running

Run this utility as follow:

```
php phpstorm_reset_trial.phar "C:\Program Files\JetBrains\PhpStorm 2018"
```

or if you use non-phar version:

```
php bin\phpstorm_reset_trial "C:\Program Files\JetBrains\PhpStorm 2018"
```

This utility will guide you along this path and will do the main job for you. When started, script will not do any changes in your system immediately. It will ask your confirmations before performing every change. No one change will be made without your understanding of it before. Typically the script output with user dialog looks as follow: 

```
PhpStorm Reset Trial
====================

This utility will reset trail period of your PhpStorm installation with saving its settings.

Config directory should be in "C:/Users/Paul/.PhpStorm/config"
Backup directory will be in "C:/Users/Paul/.PhpStorm/backup"

Want to continue? (y/n) [no] y
Backup folder already exists and it's not empty, need to clean it before continue. OK? (y/n) [yes] y
Cleaning backup folder ... OK
Move config folder to backup. PhpStorm must be closed. Are you ready? (y/n) [no] y
Moving config folder to backup ... OK
Remove Registry key "HKEY_CURRENT_USER\SOFTWARE\JavaSoft\Prefs\jetbrains\phpstorm". Continue? (y/n) [no] y
Deleting Registry ket ... OK
Now start PhpStorm and do the following things:
 - Select (*) Do not import anything -> Press [OK]
 - Press [Skip Remaining and Set Defaults]
 - Select (*) Evaluate for free -> Press [Evaluate]
 - Exit PhpStorm

Did it? (y/n) [no] y
Merging old options/options.xml with new one ... OK
Copying all other config files back from backup ... OK

All is done. Now you can start PhpStorm and continue to use it yet another 30 days! :)
```

## Building PHAR executable

You need to install [box-project](https://github.com/box-project/box2) locally by this command:

```
curl -LSs https://box-project.github.io/box2/installer.php | php
```

Open your php.ini and make
```
phar.readonly = Off
```
(it's On by default)

Now you can build a PHAR executable:

```
php box.phar build -v
```

## Excuses

PhpStorm authors made a cool product and deserve a reward. And I even have a purchased corporative license. But I don't use it because of the anti-piracy mechanism, which can lead to a denial of work in case of absence of the Internet for some time. PhpStorm needs to validate license key time to time via Internet. And if last successful license check was performed more than 2 days ago, PhpStorm stop working. If you are working outdoors without Internet access it has the risk of failing to do your work on time, deadline pass, loss of work, losses for the employer. And all this for your money, just because you wanted to live honestly. I believe that the authors should abandon such an aggressive verification of the license.
