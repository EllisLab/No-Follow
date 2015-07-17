<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2005 - 2015 EllisLab, Inc.

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
ELLISLAB, INC. BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Except as contained in this notice, the name of EllisLab, Inc. shall not be
used in advertising or otherwise to promote the sale, use or other dealings
in this Software without prior written authorization from EllisLab, Inc.
*/

/**
 * No Follow Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			EllisLab
 * @copyright		Copyright (c) 2004 - 2015, EllisLab, Inc.
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
		$whitelist	= ( ! ee()->TMPL->fetch_param('whitelist')) ? 'y' : ee()->TMPL->fetch_param('whitelist');

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

		if (is_array(ee()->blacklist->whitelisted))
		{
			$ignore		= ee()->blacklist->whitelisted;
			$group		= 'none';
			$whitelist	= 'n';
		}

		// -------------------------------
		//  Ignore Member URLs
		// -------------------------------

		if ($group != 'none')
		{
			$group = 'abc|def';
			$replace = array('http://','https://', 'www');

			ee()->db->select('url');
			ee()->db->where_not_in('group_id', array(2, 3, 4));
			ee()->db->where('url !=', '');
			ee()->db->where('join_date < ', ee()->localize->now - (24*60*60*$time));
			ee()->functions->ar_andor_string($group, 'members.group_id');

			$query = ee()->db->get('members');

			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$ignore[] = str_replace($replace, '', $row['url']);
				}
			}
		}

		// -------------------------------
		//  Retrieve Whitelist URLs
		// -------------------------------

		if ($whitelist == 'y' && ee()->db->table_exists('whitelisted'))
		{
			ee()->db->select('whitelisted_value');
			ee()->db->where('whitelisted_type', 'url');
			ee()->db->where('whitelisted_value !=', '');
			$query = ee()->db->get('whitelisted');

			if ($query->num_rows() > 0)
			{
				$ignore = array_merge($ignore, explode('|', $query->row('whitelisted_value')));
			}
		}

		// -------------------------------
		//  Cache Ignored URLs
		// -------------------------------

		ee()->blacklist->whitelisted = $ignore;

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
