<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 financialreports plugin for GLPI
 Copyright (C) 2009-2022 by the financialreports Development Team.

 https://github.com/InfotelGLPI/financialreports
 -------------------------------------------------------------------------

 LICENSE
      
 This file is part of financialreports.

 financialreports is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 financialreports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with financialreports. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define('PLUGIN_FINANCIALREPORTS_VERSION', '3.0.0');

if (!defined("PLUGIN_FINANCIALREPORTS_DIR")) {
   define("PLUGIN_FINANCIALREPORTS_DIR", Plugin::getPhpDir("financialreports"));
   // Removed second argument, only one allowed in getPhpDir
   define("PLUGIN_FINANCIALREPORTS_NOTFULL_DIR", Plugin::getPhpDir("financialreports"));
   define("PLUGIN_FINANCIALREPORTS_WEBDIR", "/plugins/financialreports/");
}

// Load Composer autoloader only if present to avoid warnings when deps are missing
if (file_exists(PLUGIN_FINANCIALREPORTS_DIR . '/vendor/autoload.php')) {
   require_once PLUGIN_FINANCIALREPORTS_DIR . '/vendor/autoload.php';
}

// Init the hooks of the plugins -Needed
function plugin_init_financialreports() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['financialreports'] = true;
   $PLUGIN_HOOKS['change_profile']['financialreports'] =
      ['PluginFinancialreportsProfile', 'initProfile'];

   if (Session::getLoginUserID()) {

      Plugin::registerClass('PluginFinancialreportsProfile',
                            ['addtabon' => 'Profile']);

      if (Session::haveRight("plugin_financialreports", READ)) {

         $PLUGIN_HOOKS['reports']['financialreports']            =
            ['front/financialreport.php' => __('Report')];
         $PLUGIN_HOOKS['use_massive_action']['financialreports'] = 1;

      }

      if (Session::haveRight("plugin_financialreports", READ)
          || Session::haveRight("config", UPDATE)) {
         $PLUGIN_HOOKS['config_page']['financialreports'] = 'front/config.form.php';
      }
   }

}

// Get the name and the version of the plugin - Needed
/**
 * @return array
 */
function plugin_version_financialreports() {

   return [
      'name'           => _n('Financial report','Financial reports',2, 'financialreports'),
      'version'        => PLUGIN_FINANCIALREPORTS_VERSION,
      'oldname'        => 'state',
      'license'        => 'GPLv2+',
      'author'         => "<a href='http://blogglpi.infotel.com'>Infotel</a>",
      'homepage'       => 'https://github.com/InfotelGLPI/financialreports',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0',
            'max' => '12.0',
            'dev' => false
         ]
      ]
   ];
}


function plugin_financialreports_check_prerequisites() {
   // GLPI 11+ version detection
   $min_version = '10.0.0';
   $max_version = '12.0';
   $glpi_version = null;
   $glpi_root = '/var/www/glpi';
   $version_dir = $glpi_root . '/version';
   if (is_dir($version_dir)) {
      $files = scandir($version_dir, SCANDIR_SORT_DESCENDING);
      foreach ($files as $file) {
         if ($file[0] !== '.' && preg_match('/^\d+\.\d+\.\d+$/', $file)) {
            $glpi_version = $file;
            break;
         }
      }
   }
   if ($glpi_version === null && defined('GLPI_VERSION')) {
      $glpi_version = GLPI_VERSION;
   }
   // Load Toolbox if not loaded
   if (!class_exists('Toolbox') && file_exists($glpi_root . '/src/Toolbox.php')) {
      require_once $glpi_root . '/src/Toolbox.php';
   }
   // Fallback error logger if Toolbox::logInFile is unavailable
   function financialreports_fallback_log($msg) {
      $logfile = __DIR__ . '/financialreports_error.log';
      $date = date('Y-m-d H:i:s');
      file_put_contents($logfile, "[$date] $msg\n", FILE_APPEND);
   }
   if (!is_readable(__DIR__ . '/vendor/autoload.php') || !is_file(__DIR__ . '/vendor/autoload.php')) {
      $logmsg = sprintf(
         'ERROR [setup.php:plugin_financialreports_check_prerequisites] Composer dependencies missing, user=%s',
         $_SESSION['glpiname'] ?? 'unknown'
      );
      if (class_exists('Toolbox') && method_exists('Toolbox', 'logInFile')) {
         Toolbox::logInFile('financialreports', $logmsg);
      } else {
         financialreports_fallback_log($logmsg);
      }
      echo "Run composer install --no-dev in the plugin directory<br>";
      return false;
   }
   if ($glpi_version === null) {
      $logmsg = '[setup.php:plugin_financialreports_check_prerequisites] ERROR: GLPI version not detected.';
      if (class_exists('Toolbox') && method_exists('Toolbox', 'logInFile')) {
         Toolbox::logInFile('financialreports', $logmsg);
      } else {
         financialreports_fallback_log($logmsg);
      }
      return false;
   }
   if (version_compare($glpi_version, $min_version, '<')) {
      $logmsg = sprintf(
         'ERROR [setup.php:plugin_financialreports_check_prerequisites] GLPI version %s is less than required minimum %s, user=%s',
         $glpi_version, $min_version, $_SESSION['glpiname'] ?? 'unknown'
      );
      if (class_exists('Toolbox') && method_exists('Toolbox', 'logInFile')) {
         Toolbox::logInFile('financialreports', $logmsg);
      } else {
         financialreports_fallback_log($logmsg);
      }
      return false;
   }
   if (version_compare($glpi_version, $max_version, '>')) {
      $logmsg = sprintf(
         'ERROR [setup.php:plugin_financialreports_check_prerequisites] GLPI version %s is greater than supported maximum %s, user=%s',
         $glpi_version, $max_version, $_SESSION['glpiname'] ?? 'unknown'
      );
      if (class_exists('Toolbox') && method_exists('Toolbox', 'logInFile')) {
         Toolbox::logInFile('financialreports', $logmsg);
      } else {
         financialreports_fallback_log($logmsg);
      }
      return false;
   }
   return true;
}
