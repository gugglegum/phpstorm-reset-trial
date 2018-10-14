# PhpStorm Reset Trial

**ATTENTION: PhpStorm is a great IDE. Its authors deserve a reward. You should buy licence, seriously. You need a very serious excuse to use this utility. Ideally, you need to have a purchased license, which you do not like to use because of the anti-piracy mechanism that requires a persistent connection to the Internet.**

This repo contains the utility which allow you to reset trial (evaluation) period in your PhpStorm installation (even if it's already expired) in a very easy and convenient way without losing your individual PhpStorm preferences.

## Resetting evaluation period

The easiest way to reset evaluation period manually is to delete PhpStorm's config folder usually located at `{user.home}/.PhpStorm{version}/config`. Additionally you need to remove PhpStorm's Java user preferences:

 * For Windows: registry key `HKEY_CURRENT_USER\SOFTWARE\JavaSoft\Prefs\jetbrains\phpstorm`
 * For Others: directory `~/.java/.userPrefs/jetbrains/phpstorm`
 
Now if you will start PhpStorm, it will think that it was just installed and started first time and will offer you to select evaluation 30 days period.

Disadvantage of this method is losing all your preferences: your hot keys, appearance, editor settings, last projects, opened files, etc. This utility is backing up your config folder, cleans PhpStorm's Java user preferences containing evaluation period info. Then after you start PhpStorm and select new evaluation period it will merge new config with old one. So you'll be able to continue working from the place where you stopped.

Actually, when merging configs we need to copy all files except `eval/*` and `options/options.xml` from backup to actual config directory. The `options/options.xml` file need to be merged more intellectually line by line with adding `<property>` XML nodes from backed up `options/options.xml` except `evlsprt*` properties.

This utility automates this process and doing all these things for you.

**Mac OS users note:** This utility was not tested yet on Mac. If the evaluation period is not resetting correctly, try to remove also this folder `~/Library/Preferences/com.jetbrains.PhpStorm.plist` before running PhpStorm to start new evaluation period. Please contact me via e-mail and tell me if removal of this folder was helped and what was inside this folder? 

## Running as PHAR archive

This is the easiest and the fastest way to reset trial period. You only need to download `phpstorm_reset_trial.phar` from [Releases](https://github.com/gugglegum/phpstorm-reset-trial/releases) section on GitHub project page into any folder and run it as follow:

```
php phpstorm_reset_trial.phar ~\.PhpStorm2018.2\config
```

The location of PhpStorm's config folder may vary from operating system and version of PhpStorm. Here are the default places where PhpStorm creates config folder: 

 * on Windows: `<USER HOME DIR>\.PhpStorm<VERSION>\config`
 * on Linux: `~/.PhpStorm<VERSION>`
 * on Mac: `~/Library/Preferences/PhpStorm<VERSION>`

This utility will guide you along the path and will do the main job for you. When started, the script will not do any changes in your system immediately. It will ask your confirmations before performing every change. No one change will be made without your understanding of it before. Typically the script output with user dialog looks as follow: 

```
$ php bin/phpstorm_reset_trial ~/.PhpStorm2018.2/config/
PhpStorm Reset Trial ver. 1.0.0 2018-09-26

This utility will reset trial period of your PhpStorm installation with saving its settings.

Config directory in "/home/paul/.PhpStorm2018.2/config"
Backup directory in "/home/paul/.PhpStorm2018.2/backup"

Want to continue? (y/n) [no] y
Move config folder to backup. PhpStorm must be closed. Are you ready? (y/n) [no] y
Moving config folder to backup ... OK
Remove folder ~/.java/.userPrefs/jetbrains/phpstorm? (y/n) [no] y
Making backup ... OK
Removing ... OK
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

## Running not as PHAR archive

This way mostly interested by developers of this project. Most users should run it as PHAR archive described in previous section as it's more simple. To run this utility without PHAR you need to have [Composer](https://getcomposer.org/) installed.

You need to download project files and run in its root:  

```
composer install
```

This utility actually doesn't uses any third-party packages, it uses Composer just for class autoloader. Now you can run it so:

```
php bin/phpstorm_reset_trial ~\.PhpStorm2018.2\config
```

## Building PHAR executable

If you want to build PHAR archive by yourself, at first you need to make it runnable as described in the previous section. Then you need to install [box-project](https://github.com/box-project/box2) (PHAR archive generator) by this command executed in the project root:

```
curl -LSs https://box-project.github.io/box2/installer.php | php
```

Then open your php.ini and make
```
phar.readonly = Off
```

Now you can build a PHAR executable:

```
php box.phar build -v
```

In the project root directory you will see the `phpstorm_reset_trial.phar`. 

## Excuses

PhpStorm authors made a cool product and deserve a reward. And I even have a purchased corporative license. But I don't use it because of the anti-piracy mechanism, which can lead to a denial of work in case of absence of the Internet for some time. PhpStorm needs to validate license key time to time via Internet. And if last successful license check was performed more than 2 days ago, PhpStorm stop working. If you are working outdoors without Internet access it has the risk of failing to do your work on time, deadline pass, loss of work, losses for the employer. And all this for your money, just because you wanted to live honestly. I believe that the authors should abandon such an aggressive verification of the license.
