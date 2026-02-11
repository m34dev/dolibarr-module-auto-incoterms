<?php
/* Copyright (C) 2026       William Mead    <william@m34d.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    autoincoterms/admin/autoincoterms.php
 * \ingroup autoincoterms
 * \brief   AutoIncoterms admin page
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/autoincoterms.lib.php';
require_once '../class/autoincoterms.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "autoincoterms@autoincoterms"));

// Initialize a technical object to manage hooks of page
$hookmanager->initHooks(array('autoincotermsadmin'));

// Parameters
$action = GETPOST('action', 'aZ09');

// Access control
if (!$user->admin) {
	accessforbidden();
}

/*
 * Actions
 */

if ($action === 'updateallclients') {
	if (!verifCond(newToken() == GETPOST('token', 'alpha'))) {
		accessforbidden('Bad value for CSRF token');
	}

	$autoIncoterms = new AutoIncoterms($db);
	$results = $autoIncoterms->updateIncotermsForAllActiveClients();

	if ($results === -1) {
		setEventMessages($langs->trans('AutoIncotermsErrorDatabase'), null, 'errors');
	} else {
		$successCount = $results['success'];
		$errorCount = count($results['errors']);
		if ($successCount > 0) {
			setEventMessages($langs->trans('AutoIncotermsUpdateAllSuccess', $successCount), null, 'mesgs');
		}
		if ($errorCount > 0) {
			setEventMessages($langs->trans('AutoIncotermsUpdateAllErrors', $errorCount), null, 'warnings');
		}
	}

	$action = '';
}

if ($action === 'updateallclientsdefault') {
	if (!verifCond(newToken() == GETPOST('token', 'alpha'))) {
		accessforbidden('Bad value for CSRF token');
	}

	$defaultIncotermsId = getDolGlobalInt('AUTOINCOTERMS_DEFAULT_INCOTERM');
	if (empty($defaultIncotermsId)) {
		setEventMessages($langs->trans('AutoIncotermsErrorNoDefault'), null, 'errors');
	} else {
		$autoIncoterms = new AutoIncoterms($db);
		$results = $autoIncoterms->updateIncotermsForAllActiveClients($defaultIncotermsId, null, true);

		if ($results === -1) {
			setEventMessages($langs->trans('AutoIncotermsErrorDatabase'), null, 'errors');
		} else {
			$successCount = $results['success'];
			$errorCount = count($results['errors']);
			if ($successCount > 0) {
				setEventMessages($langs->trans('AutoIncotermsUpdateAllSuccess', $successCount), null, 'mesgs');
			}
			if ($errorCount > 0) {
				setEventMessages($langs->trans('AutoIncotermsUpdateAllErrors', $errorCount), null, 'warnings');
			}
		}
	}

	$action = '';
}

if ($action === 'resetallclients') {
	if (!verifCond(newToken() == GETPOST('token', 'alpha'))) {
		accessforbidden('Bad value for CSRF token');
	}

	$autoIncoterms = new AutoIncoterms($db);
	$results = $autoIncoterms->updateIncotermsForAllActiveClients(0, '');

	if ($results === -1) {
		setEventMessages($langs->trans('AutoIncotermsErrorDatabase'), null, 'errors');
	} else {
		$successCount = $results['success'];
		$errorCount = count($results['errors']);
		if ($successCount > 0) {
			setEventMessages($langs->trans('AutoIncotermsResetAllSuccess', $successCount), null, 'mesgs');
		}
		if ($errorCount > 0) {
			setEventMessages($langs->trans('AutoIncotermsResetAllErrors', $errorCount), null, 'warnings');
		}
	}

	$action = '';
}

/*
 * View
 */

$form = new Form($db);

// Fetch default incoterm code for display
$defaultIncotermCode = '';
$defaultIncotermId = getDolGlobalInt('AUTOINCOTERMS_DEFAULT_INCOTERM');
if ($defaultIncotermId > 0) {
	$sql = "SELECT code FROM ".$db->prefix()."c_incoterms WHERE rowid = ".((int) $defaultIncotermId);
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql))) {
		$defaultIncotermCode = $obj->code;
	}
	$db->free($resql);
}

$help_url = '';
$title = "AutoIncoterms";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-autoincoterms page-admin-autoincoterms');

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'object_autoincoterms@autoincoterms');

// Configuration header
$head = autoincotermsAdminPrepareHead();
print dol_get_fiche_head($head, 'autoincoterms', $langs->trans($title), -1, "setup");

// Page content goes here
print '<span class="opacitymedium">'.$langs->trans("AutoIncotermsPage").'</span><br><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AutoIncotermsUpdateAllClientsTitle").'</td>';
print '<td class="right"></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AutoIncotermsUpdateAllClientsDesc").'</td>';
print '<td class="right">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display:inline">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateallclients">';
print '<input type="submit" class="button" value="'.$langs->trans("AutoIncotermsUpdateAllClientsButton").'">';
print '</form>';
print '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AutoIncotermsUpdateAllDefaultDesc").(!empty($defaultIncotermCode) ? ': <strong>'.$defaultIncotermCode.'</strong>' : '').'</td>';
print '<td class="right">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display:inline">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateallclientsdefault">';
$defaultButtonLabel = $langs->trans("AutoIncotermsUpdateAllDefaultButton");
print '<input type="submit" class="button" value="'.$defaultButtonLabel.'"'.($defaultIncotermId > 0 ? '' : ' disabled').'>';
print '</form>';
print '</td>';
print '</tr>';
print '</table>';

print '<br>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="resetallclients">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AutoIncotermsResetAllClientsTitle").'</td>';
print '<td class="right"></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AutoIncotermsResetAllClientsDesc").'</td>';
print '<td class="right"><input type="submit" class="butActionDelete" value="'.$langs->trans("AutoIncotermsResetAllClientsButton").'"></td>';
print '</tr>';
print '</table>';
print '</form>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();