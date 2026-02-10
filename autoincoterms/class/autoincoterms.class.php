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
		$this->db = $db;
	}

	/**
	 * Set incoterms location for a client based on their city and country
	 *
	 * @param int $clientId ID of the client (third party)
	 * @return int 1 if success with city and country, 2 if success with city only, 3 if success with country only, -1 if client not found or update failed, -2 if no city and no country
	 */
	public function setIncotermsLocationFromClientAddress($clientId)
	{
		$societe = new Societe($this->db);

		$result = $societe->fetch($clientId);
		if ($result <= 0) {
			$this->error = 'Client not found';
			return -1;
		}

		$hasCity = !empty($societe->town);
		$hasCountry = !empty($societe->country);

		if (!$hasCity && !$hasCountry) {
			$this->error = 'Client has no city and no country';
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

		$result = $societe->setIncoterms($societe->fk_incoterms, $location);
		if ($result < 0) {
			$this->error = $societe->error;
			return -1;
		}

		return $returnCode;
	}
}
