<?php
/* Copyright (C) 2026		William Mead			<william@m34d.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        htdocs/autoincoterms/class/autoincoterms.class.php
 * \ingroup     autoincoterms
 * \brief       Business logic for AutoIncoterms module
 */

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * Class for AutoIncoterms
 */
class AutoIncoterms
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error message
	 */
	public $error;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * Resolve incoterms ID and location from a client's address
	 *
	 * @param int         $clientId     ID of the client (third party)
	 * @param int|null    $incotermsId  Optional incoterms ID to set (null to keep existing)
	 * @param string|null $locationText Optional location text to set (null to derive from client address)
	 * @return array|int Array with keys 'fk_incoterms', 'location', 'code' on success, or negative int on error: -1 client not found, -2 no city and no country, -3 not a client or prospect, -4 no incoterms and no default
	 */
	protected function resolveIncotermsFromClient($clientId, $incotermsId = null, $locationText = null)
	{
		global $langs;
		$langs->load("autoincoterms@autoincoterms");

		dol_syslog(__METHOD__." clientId=".$clientId." incotermsId=".$incotermsId." locationText=".$locationText, LOG_DEBUG);

		$societe = new Societe($this->db);

		$result = $societe->fetch($clientId);
		if ($result <= 0) {
			$this->error = $langs->trans('AutoIncotermsErrorClientNotFound');
			dol_syslog(__METHOD__." error=".$this->error, LOG_ERR);
			return -1;
		}

		if ($societe->client == 0) {
			$this->error = $langs->trans('AutoIncotermsErrorNotClientOrProspect');
			dol_syslog(__METHOD__." error=".$this->error, LOG_WARNING);
			return -3;
		}

		// Use provided incoterms ID or keep existing, fallback to module default
		$fkIncoterms = ($incotermsId !== null) ? $incotermsId : $societe->fk_incoterms;
		if (empty($fkIncoterms)) {
			$fkIncoterms = getDolGlobalInt('AUTOINCOTERMS_DEFAULT_INCOTERM');
			if (empty($fkIncoterms)) {
				$this->error = $langs->trans('AutoIncotermsErrorNoIncotermAndNoDefault');
				dol_syslog(__METHOD__." error=".$this->error, LOG_WARNING);
				return -4;
			}
			dol_syslog(__METHOD__." client has no incoterms, using default fk_incoterms=".$fkIncoterms, LOG_DEBUG);
		}

		// Use provided location text or derive from client address
		if ($locationText !== null) {
			$location = $locationText;
			$returnCode = 4;
		} else {
			$hasCity = !empty($societe->town);
			$hasCountry = !empty($societe->country);

			if (!$hasCity && !$hasCountry) {
				$this->error = $langs->trans('AutoIncotermsErrorNoCityNoCountry');
				dol_syslog(__METHOD__." error=".$this->error, LOG_WARNING);
				return -2;
			}

			$town = dol_strtoupper($societe->town);
			$country = dol_strtoupper($societe->country);

			if ($hasCity && $hasCountry) {
				$location = $town.' - '.$country;
				$returnCode = 1;
			} elseif ($hasCity) {
				$location = $town;
				$returnCode = 2;
			} else {
				$location = $country;
				$returnCode = 3;
			}
		}

		dol_syslog(__METHOD__." resolved fk_incoterms=".$fkIncoterms." location=".$location." code=".$returnCode, LOG_DEBUG);

		return array('fk_incoterms' => $fkIncoterms, 'location' => $location, 'code' => $returnCode);
	}

	/**
	 * Set incoterms location for a client based on their city and country
	 *
	 * @param int      $clientId     ID of the client (third party)
	 * @param int|null $incotermsId  Optional incoterms ID to set (null to keep existing)
	 * @param string|null $locationText Optional location text to set (null to derive from client address)
	 * @return int 1 if success with city and country, 2 if success with city only, 3 if success with country only, 4 if success with provided location text, -1 if client not found or update failed, -2 if no city and no country, -3 if third party is not a client or prospect, -4 if no incoterms set and no default configured
	 */
	public function setIncotermsLocationFromClientAddress($clientId, $incotermsId = null, $locationText = null)
	{
		dol_syslog(__METHOD__." clientId=".$clientId, LOG_DEBUG);

		$resolved = $this->resolveIncotermsFromClient($clientId, $incotermsId, $locationText);
		if (!is_array($resolved)) {
			return $resolved;
		}

		$societe = new Societe($this->db);
		$societe->fetch($clientId);

		$result = $societe->setIncoterms($resolved['fk_incoterms'], $resolved['location']);
		if ($result < 0) {
			$this->error = $societe->error;
			dol_syslog(__METHOD__." error=".$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog(__METHOD__." success, returnCode=".$resolved['code'], LOG_DEBUG);

		return $resolved['code'];
	}

	/**
	 * Set incoterms on a commercial document (propal, order, invoice, etc.) based on client address
	 *
	 * @param CommonObject $object       The document object (Propal, Commande, Facture, etc.)
	 * @param int          $clientId     ID of the client (third party)
	 * @param int|null     $incotermsId  Optional incoterms ID to set (null to keep existing)
	 * @param string|null  $locationText Optional location text to set (null to derive from client address)
	 * @return int 1 if success with city and country, 2 if success with city only, 3 if success with country only, 4 if success with provided location text, -1 if client not found or update failed, -2 if no city and no country, -3 if third party is not a client or prospect, -4 if no incoterms set and no default configured
	 */
	public function setDocumentIncotermsFromClientAddress($object, $clientId, $incotermsId = null, $locationText = null)
	{
		dol_syslog(__METHOD__." objectClass=".get_class($object)." objectId=".$object->id." clientId=".$clientId, LOG_DEBUG);

		$resolved = $this->resolveIncotermsFromClient($clientId, $incotermsId, $locationText);
		if (!is_array($resolved)) {
			return $resolved;
		}

		$result = $object->setIncoterms($resolved['fk_incoterms'], $resolved['location']);
		if ($result < 0) {
			$this->error = $object->error;
			dol_syslog(__METHOD__." error=".$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog(__METHOD__." success, returnCode=".$resolved['code'], LOG_DEBUG);

		return $resolved['code'];
	}

	/**
	 * Update incoterms location for a list of clients
	 *
	 * @param array       $clientIds    Array of client IDs
	 * @param int|null    $incotermsId  Optional incoterms ID to set for all clients (null to keep existing)
	 * @param string|null $locationText Optional location text to set for all clients (null to derive from client address)
	 * @return array Associative array with results: 'success' => count, 'errors' => array of [id => error code]
	 */
	public function updateIncotermsForClients($clientIds, $incotermsId = null, $locationText = null)
	{
		dol_syslog(__METHOD__." called with ".count($clientIds)." clients, incotermsId=".$incotermsId." locationText=".$locationText, LOG_DEBUG);

		$results = array(
			'success' => 0,
			'errors' => array()
		);

		foreach ($clientIds as $clientId) {
			$result = $this->setIncotermsLocationFromClientAddress($clientId, $incotermsId, $locationText);
			if ($result > 0) {
				$results['success']++;
			} else {
				$results['errors'][$clientId] = $result;
			}
		}

		dol_syslog(__METHOD__." completed: ".$results['success']." success, ".count($results['errors'])." errors", LOG_DEBUG);

		return $results;
	}

	/**
	 * Update incoterms location for all active clients and prospects
	 *
	 * @param int|null    $incotermsId  Optional incoterms ID to set for all clients (null to keep existing)
	 * @param string|null $locationText Optional location text to set for all clients (null to derive from client address)
	 * @return array|int Associative array with results: 'success' => count, 'errors' => array of [id => error code], or -1 on database error
	 */
	public function updateIncotermsForAllActiveClients($incotermsId = null, $locationText = null)
	{
		dol_syslog(__METHOD__." called, incotermsId=".$incotermsId." locationText=".$locationText, LOG_DEBUG);

		$clientIds = $this->fetchAllActiveClientIds();
		if ($clientIds === -1) {
			dol_syslog(__METHOD__." error fetching client IDs", LOG_ERR);
			return -1;
		}

		return $this->updateIncotermsForClients($clientIds, $incotermsId, $locationText);
	}

	/**
	 * Fetch all active client and prospect IDs
	 *
	 * @return array|int Array of client IDs, or -1 on error
	 */
	protected function fetchAllActiveClientIds()
	{
		dol_syslog(__METHOD__." called", LOG_DEBUG);

		$clientIds = array();

		$sql = "SELECT t.rowid FROM ".MAIN_DB_PREFIX."societe as t";
		$sql .= " WHERE t.status = 1";
		$sql .= " AND t.client IN (1, 2, 3)";

		dol_syslog(__METHOD__." sql=".$sql, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__." error=".$this->error, LOG_ERR);
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$clientIds[] = $obj->rowid;
		}
		$this->db->free($resql);

		dol_syslog(__METHOD__." found ".count($clientIds)." clients", LOG_DEBUG);

		return $clientIds;
	}
}
