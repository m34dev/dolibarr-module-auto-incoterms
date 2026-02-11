<?php
/* Copyright (C) 2026       William Mead    <william@m34d.com>
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

use Luracast\Restler\RestException;

dol_include_once('/autoincoterms/class/autoincoterms.class.php');



/**
 * \file    htdocs/autoincoterms/class/api_autoincoterms.class.php
 * \ingroup autoincoterms
 * \brief   API for AutoIncoterms module
 */

/**
 * API class for autoincoterms
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class AutoIncotermsApi extends DolibarrApi
{
	
	/**
	 * Constructor
	 *
	 * @url     GET /
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}
	
	/**
	 * Update incoterms for a third party based on its address
	 *
	 * @param	int		$id		Third party ID
	 * @return	array			Result with incoterms code and location
	 *
	 * @url	PUT {id}/incoterms
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 500 Error
	 */
	public function updateThirdPartyIncoterms($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403, 'Not allowed to edit third parties');
		}

		$autoIncoterms = new AutoIncoterms($this->db);
		$result = $autoIncoterms->setIncotermsLocationFromClientAddress($id);

		if ($result <= 0) {
			throw new RestException(500, $autoIncoterms->error);
		}

		return array(
			'success' => true,
			'socid' => $id
		);
	}
}