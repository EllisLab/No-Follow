<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2005 - 2011 EllisLab, Inc.

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

$plugin_info = array(
						'pi_name'			=> 'No Follow',
						'pi_version'		=> '1.1',
						'pi_author'			=> 'Paul Burdick',
						'pi_author_url'		=> 'http://www.expressionengine.com/',
						'pi_description'	=> 'Gives links the rel="nofollow" attribute',
						'pi_usage'			=> No_follow::usage()
					);

/**
 * No Follow Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			ExpressionEngine Dev Team
 * @copyright		Copyright (c) 2005 - 2011, EllisLab, Inc.
 * @link			http://expressionengine.com/downloads/details/no_follow/
 */
class No_follow {

	var $return_data;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	function No_follow($str = '')
	{
        $EE =& get_instance();

		// -------------------------------
		//  Fetch Parameters
		// -------------------------------

		$group		= ( ! $EE->TMPL->fetch_param('group')) ? '1' : $EE->TMPL->fetch_param('group');
		$time		= ( ! $EE->TMPL->fetch_param('time')) ? '1' : $EE->TMPL->fetch_param('time'); // In days
		$whitelist	= ( ! $EE->TMPL->fetch_param('whitelist')) ? 'y' : $EE->TMPL->fetch_param('whitelist');

		// -------------------------------
		//  Fetch and Check Our Content
		// -------------------------------

		$template = ($str == '') ? $EE->TMPL->tagdata : $str;

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

		if (is_array($EE->blacklist->whitelisted))
		{
			$ignore		= $EE->blacklist->whitelisted;
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
			
			$EE->db->select('url');
			$EE->db->where_not_in('group_id', array(2, 3, 4));
			$EE->db->where('url !=', '');
			$EE->db->where('join_date < ', $EE->localize->now - (24*60*60*$time));
			$EE->functions->ar_andor_string($group, 'members.group_id');
			
			$query = $EE->db->get('members');
        			
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

		if ($whitelist == 'y' && $EE->db->table_exists('whitelisted'))
		{
			$EE->db->select('whitelisted_value');
			$EE->db->where('whitelisted_type', 'url');
			$EE->db->where('whitelisted_value !=', '');
			$query = $EE->db->get('whitelisted');

			if ($query->num_rows() > 0)
			{
				$ignore = array_merge($ignore, explode('|', $query->row('whitelisted_value')));
			}
		}
		
		// -------------------------------
		//  Cache Ignored URLs
		// -------------------------------

		$EE->blacklist->whitelisted = $ignore;

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
	
	// --------------------------------------------------------------------
	
	/**
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string	plugin usage text
	 */
	function usage()
	{
		ob_start(); 
		?>

		Looks for hyperlinks in the text and adds rel="nofollow" attribute to them

		{exp:no_follow}

		A &lt;a href="http://www.evilsite.com">link&lt;/a> from Spammers

		{/exp:no_follow}

		// Returns 'A &lt;a href="http://www.evilsite.com" rel="nofollow">link&lt;/a> from Spammers;

		PARAMETERS
		----------------------------------------------
		group - Allows you to specify member groups whose members' URLs will be ignored by this plugin.

		time - Allows you to specify how much time (in days) a member account must be active before its member url will be ignored.
		This allows newly created member accounts to be reviewed for a short period before their urls are ignored. Only works if the group
		parameter is set.

		whitelist - (y/n) Allows you to use the ExpressionEngine Whitelist to ignore URLs that are whitelisted.


		Version 1.1
		******************
		- Updated plugin to be 2.0 compatible


		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.no_follow.php */
/* Location: ./system/expressionengine/no_follow/pi.no_follow.php */