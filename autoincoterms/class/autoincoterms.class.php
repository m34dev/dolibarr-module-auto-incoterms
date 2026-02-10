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
	 * Set incoterms location for a client based on their city and country
	 *
	 * @param int $clientId ID of the client (third party)
	 * @return int 1 if success with city and country, 2 if success with city only, 3 if success with country only, -1 if client not found or update failed, -2 if no city and no country, -3 if third party is not a client or prospect
	 */
	public function setIncotermsLocationFromClientAddress($clientId)
	{
		dol_syslog(__METHOD__." clientId=".$clientId, LOG_DEBUG);

		$societe = new Societe($this->db);

		$result = $societe->fetch($clientId);
		if ($result <= 0) {
			$this->error = 'Client not found';
			dol_syslog(__METHOD__." error=".$this->error, LOG_ERR);
			return -1;
		}

		if ($societe->client == 0) {
			$this->error = 'Third party is not a client or prospect';
			dol_syslog(__METHOD__." error=".$this->error, LOG_WARNING);
			return -3;
		}

		$hasCity = !empty($societe->town);
		$hasCountry = !empty($societe->country);

		if (!$hasCity && !$hasCountry) {
			$this->error = 'Client has no city and no country';
			dol_syslog(__METHOD__." error=".$this->error, LOG_WARNING);
			return -2;
		}

		if ($hasCity && $hasCountry) {
			$location = $societe->town.', '.$societe->country;
			$returnCode = 1;
		} elseif ($hasCity) {
			$location = $societe->town;
			$returnCode = 2;
		} else {
			$location = $societe->country;
			$returnCode = 3;
		}

		dol_syslog(__METHOD__." setting location=".$location, LOG_DEBUG);

		$result = $societe->setIncoterms($societe->fk_incoterms, $location);
		if ($result < 0) {
			$this->error = $societe->error;
			dol_syslog(__METHOD__." error=".$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog(__METHOD__." success, returnCode=".$returnCode, LOG_DEBUG);

		return $returnCode;
	}

	/**
	 * Update incoterms location for a list of clients
	 *
	 * @param array $clientIds Array of client IDs
	 * @return array Associative array with results: 'success' => count, 'errors' => array of [id => error code]
	 */
	public function updateIncotermsForClients($clientIds)
	{
		dol_syslog(__METHOD__." called with ".count($clientIds)." clients", LOG_DEBUG);

		$results = array(
			'success' => 0,
			'errors' => array()
		);

		foreach ($clientIds as $clientId) {
			$result = $this->setIncotermsLocationFromClientAddress($clientId);
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
	 * @return array|int Associative array with results: 'success' => count, 'errors' => array of [id => error code], or -1 on database error
	 */
	public function updateIncotermsForAllActiveClients()
	{
		dol_syslog(__METHOD__." called", LOG_DEBUG);

		$clientIds = $this->fetchAllActiveClientIds();
		if ($clientIds === -1) {
			dol_syslog(__METHOD__." error fetching client IDs", LOG_ERR);
			return -1;
		}

		return $this->updateIncotermsForClients($clientIds);
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
