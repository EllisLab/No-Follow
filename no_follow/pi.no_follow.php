<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2005 - 2021 Packet Tide, LLC

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
PACKET TIDE, LLC BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Except as contained in this notice, the name of Packet Tide, LLC shall not be
used in advertising or otherwise to promote the sale, use or other dealings
in this Software without prior written authorization from Packet Tide, LLC.
*/

/**
 * No Follow Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Packet Tide
 * @copyright		Copyright (c) 2005 - 2021 Packet Tide, LL
 * @link			https://github.com/EllisLab/No-Follow
 */
class No_follow {

	public $return_data;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct($str = '')
	{
		// -------------------------------
		//  Fetch Parameters
		// -------------------------------

		$group		= ( ! ee()->TMPL->fetch_param('group')) ? '1' : ee()->TMPL->fetch_param('group');
		$time		= ( ! ee()->TMPL->fetch_param('time')) ? '1' : ee()->TMPL->fetch_param('time'); // In days
		$allowedlist	= ( ! ee()->TMPL->fetch_param('allowedlist')) ? 'y' : ee()->TMPL->fetch_param('allowedlist');

		// -------------------------------
		//  Fetch and Check Our Content
		// -------------------------------

		$template = ($str == '') ? ee()->TMPL->tagdata : $str;

		// Opening Link Tag?
		if (stristr(str_replace('&lt;', '<', $template), '<a') === false)
		{
			$this->return_data = $template;
			return;
		}

		// -------------------------------
		//  Semi-Clever Caching
		// -------------------------------

		$ignore = array();

		if (is_array(ee()->blockedlist->allowed))
		{
			$ignore		= ee()->blockedlist->allowed;
			$group		= 'none';
			$allowedlist	= 'n';
		}

		// -------------------------------
		//  Retrieve allowedlist URLs
		// -------------------------------

		if ($allowedlist == 'y' && ee()->db->table_exists('allowedlist'))
		{
			ee()->db->select('allowedlist_value');
			ee()->db->where('allowedlist_type', 'url');
			ee()->db->where('allowedlist_value !=', '');
			$query = ee()->db->get('allowedlist');

			if ($query->num_rows() > 0)
			{
				$ignore = array_merge($ignore, explode('|', $query->row('allowedlist_value')));
			}
		}

		// -------------------------------
		//  Cache Ignored URLs
		// -------------------------------

		ee()->blockedlist->allowed = $ignore;

		// -------------------------------
		//  Search and Modify URLs
		// -------------------------------

		if (preg_match_all('/href="(.+?)"/is', $template, $matches))
		{
			for($i=0; $i < count($matches['0']); $i++)
			{
				$doit = 'y';

				if (count($ignore) > 0)
				{
					foreach($ignore as $good)
					{
						if (stristr($matches['1'][$i], $good) !== false)
						{
							$doit = 'n';
							break;
						}
					}
				}

				if ($doit == 'y')
				{
					$template = str_replace($matches['0'][$i], $matches['0'][$i].' rel="nofollow"', $template);
				}
			}
		}

		// -------------------------------
		//  Finished!
		// -------------------------------

		$this->return_data = $template;
	}
}