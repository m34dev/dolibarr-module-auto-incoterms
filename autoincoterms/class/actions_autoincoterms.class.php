<?php
/* Copyright (C) 2026		William Mead		<william@m34d.com>
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
 * \file    htdocs/autoincoterms/class/actions_autoincoterms.class.php
 * \ingroup autoincoterms
 * \brief   Hooks
 */

require_once "autoincoterms.class.php";

/**
 * Class ActionsAutoIncoterms
 */
class ActionsAutoIncoterms
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';
	
	/**
	 * @var string[] Errors
	 */
	public $errors = array();
	
	
	/**
	 * @var mixed[] Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();
	
	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;
	
	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;
	
	
	/**
	 * Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}
	
	/**
	 * Overloading the formObjectOptions function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load("autoincoterms@autoincoterms");
		return 0;
	}
	
	/**
	 * Overloading the printFieldListOption function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListOption($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load("autoincoterms@autoincoterms");
		return 0;
	}

	/**
	 * Overloading the printFieldListTitle function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListTitle($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load("autoincoterms@autoincoterms");
		return 0;
	}
	
	/**
	 * Overloading the printFieldListValue function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListValue($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load("autoincoterms@autoincoterms");
		return 0;
	}

	/**
	 * Overloading the loadStaticObject function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function loadStaticObject($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load("autoincoterms@autoincoterms");
		return 0;
	}

	/**
	 * Add action button on propal, order, invoice cards
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	CommonObject	&$object		The object to process (Propal, Commande, Facture)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;
		$langs->load("autoincoterms@autoincoterms");
		
		$allowedContexts = array('thirdpartycard', 'propalcard', 'ordercard', 'invoicecard', 'expeditioncard', 'invoicereccard');

		if (array_intersect($allowedContexts, $hookmanager->contextarray)) {
			$url = $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=autoincoterms&token='.newToken();
			print dolGetButtonAction('', $langs->trans("RefreshAutoIncoterms"), 'default', $url, '', 1);
		}

		return 0;
	}

	/**
	 * Execute action from hook
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	CommonObject	&$object		The object to process (Propal, Commande, Facture, etc.)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;
		$langs->load("autoincoterms@autoincoterms");

		if ($action === 'autoincoterms') {
			dol_syslog(get_class($this)."::doActions action=autoincoterms, context=".implode(',', $hookmanager->contextarray), LOG_DEBUG);

			$allowedContexts = array('thirdpartycard', 'propalcard', 'ordercard', 'invoicecard', 'expeditioncard', 'invoicereccard');

			if (array_intersect($allowedContexts, $hookmanager->contextarray)) {
				if (in_array('thirdpartycard', $hookmanager->contextarray)) {
					$socid = $object->id;
				} else {
					$socid = !empty($object->socid) ? $object->socid : "";
				}
				dol_syslog(get_class($this)."::doActions processing object id=".$object->id." socid=".$socid, LOG_DEBUG);

				$autoIncoterms = new AutoIncoterms($this->db);

				// Update third party incoterms
				$result = $autoIncoterms->setIncotermsLocationFromClientAddress($socid);
				if ($result > 0) {
					dol_syslog(get_class($this)."::doActions setIncotermsLocationFromClientAddress success for socid=".$socid, LOG_INFO);
					setEventMessages($langs->trans("AutoIncotermsClientSuccess"), null, 'mesgs');
				} else {
					dol_syslog(get_class($this)."::doActions setIncotermsLocationFromClientAddress failed for socid=".$socid." error=".$autoIncoterms->error, LOG_ERR);
					setEventMessages($autoIncoterms->error, null, 'errors');
				}

				// Update document incoterms (propal, order, invoice, etc.)
				if (!in_array('thirdpartycard', $hookmanager->contextarray)) {
					$resultDoc = $autoIncoterms->setDocumentIncotermsFromClientAddress($object, $socid);
					if ($resultDoc > 0) {
						dol_syslog(get_class($this)."::doActions setDocumentIncotermsFromClientAddress success for object id=".$object->id, LOG_INFO);
						setEventMessages($langs->trans("AutoIncotermsDocumentSuccess"), null, 'mesgs');
					} else {
						dol_syslog(get_class($this)."::doActions setDocumentIncotermsFromClientAddress failed for object id=".$object->id." error=".$autoIncoterms->error, LOG_ERR);
						setEventMessages($autoIncoterms->error, null, 'errors');
					}
				}

				$action = '';
			} else {
				dol_syslog(get_class($this)."::doActions context not allowed: ".implode(',', $hookmanager->contextarray), LOG_WARNING);
			}
		}

		return 0;
	}
}
