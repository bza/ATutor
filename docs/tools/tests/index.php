<?php
/****************************************************************/
/* ATutor														*/
/****************************************************************/
/* Copyright (c) 2002-2004 by Greg Gay & Joel Kronenberg        */
/* Adaptive Technology Resource Centre / University of Toronto  */
/* http://atutor.ca												*/
/*                                                              */
/* This program is free software. You can redistribute it and/or*/
/* modify it under the terms of the GNU General Public License  */
/* as published by the Free Software Foundation.				*/
/****************************************************************/

	define('AT_INCLUDE_PATH', '../../include/');
	require(AT_INCLUDE_PATH.'vitals.inc.php');
	$_section[0][0] = _AT('tools');
	$_section[0][1] = 'tools/';
	$_section[1][0] = _AT('test_manager');

	if (!authenticate(AT_PRIV_TEST_CREATE, AT_PRIV_RETURN) && !authenticate(AT_PRIV_TEST_MARK, AT_PRIV_RETURN)) {
		exit;
	}

	require(AT_INCLUDE_PATH.'header.inc.php');
	
	echo '<h2>';
	if ($_SESSION['prefs'][PREF_CONTENT_ICONS] != 2) {
		echo '<a href="tools/" class="hide"><img src="images/icons/default/square-large-tools.gif"  class="menuimageh2" border="0" vspace="2" width="42" height="40" alt="" /></a>';
	}
	if ($_SESSION['prefs'][PREF_CONTENT_ICONS] != 1) {
		echo ' <a href="tools/" class="hide">'._AT('tools').'</a>';
	}
echo '</h2>';

echo '<h3>';
	if ($_SESSION['prefs'][PREF_CONTENT_ICONS] != 2) {
		echo '&nbsp;<img src="images/icons/default/test-manager-large.gif"  class="menuimageh3" width="42" height="38" alt="" /> ';
	}
	if ($_SESSION['prefs'][PREF_CONTENT_ICONS] != 1) {
		echo _AT('test_manager');
	}
echo '</h3>';

if (authenticate(AT_PRIV_TEST_CREATE, AT_PRIV_RETURN)) {
	$help[] = AT_HELP_ADD_TEST1;
	print_help($help);

	echo '<p>&nbsp;&nbsp;&nbsp;&nbsp;[<a href="tools/tests/add_test.php">'._AT('add_test').'</a>]<br /></p>';
}

	
	/* get a list of all the tests we have, and links to create, edit, delete, preview */

	$sql	= "SELECT *, UNIX_TIMESTAMP(start_date) AS us, UNIX_TIMESTAMP(end_date) AS ue FROM ".TABLE_PREFIX."tests WHERE course_id=$_SESSION[course_id] ORDER BY start_date DESC";
	$result	= mysql_query($sql, $db);
	$num_tests = mysql_num_rows($result);

	echo '<table cellspacing="1" cellpadding="0" border="0" class="bodyline" summary="" align="center">';
	echo '<tr>';
	echo '<th scope="col"><small>'._AT('status').'</small></th>';
	echo '<th scope="col"><small>'._AT('title').'</small></th>';
	echo '<th scope="col"><small>'._AT('availability').'</small></th>';
	echo '<th scope="col"><small>'._AT('questions').'</small></th>';
	echo '<th scope="col"><small>'._AT('results').'</small></th>';
	$cols=9;
	if (authenticate(AT_PRIV_TEST_CREATE, AT_PRIV_RETURN)) {
		echo '<th scope="col"><small>'._AT('edit').' &amp; '._AT('delete').'</small></th>';
		$cols--;
	}
	echo '</tr>';

	if ($row = mysql_fetch_array($result)) {
		do {
			$count++;
			echo '<tr>';
			echo '<td class="row1"><small>';
			if ( ($row['us'] <= time()) && ($row['ue'] >= time() ) ) {
				echo '<em>'._AT('ongoing').'</em>';
			} else if ($row['ue'] < time() ) {
				echo '<em>'._AT('expired').'</em>';
			} else if ($row['us'] > time() ) {
				echo '<em>'._AT('pending').'</em>';
			}
			echo '</small></td>';
			echo '<td class="row1"><small>'.$row['title'].'</small></td>';
			echo '<td class="row1"><small>'.AT_date('%j/%n/%y %G:%i', $row['start_date'], AT_DATE_MYSQL_DATETIME).'<br />'._AT('to_2').' ';
			echo AT_date('%j/%n/%y %G:%i', $row['end_date'], AT_DATE_MYSQL_DATETIME).'</small></td>';
			echo '<td class="row1"><small>';
			$sql	= "SELECT COUNT(*) FROM ".TABLE_PREFIX."tests_questions WHERE test_id=$row[test_id]";
			$result2= mysql_query($sql, $db);
			$row2	= mysql_fetch_array($result2);
			echo '&middot; <a href="tools/tests/questions.php?tid='.$row['test_id'].SEP.'tt='.$row['title'].'">'.$row2[0]. ' '._AT('questions').'</a></small></td>';
			echo '<td class="row1"><small>';

			/************************/
			/* Unmarked				*/
			$sql	= "SELECT COUNT(*) FROM ".TABLE_PREFIX."tests_results WHERE test_id=$row[test_id] AND final_score=''";
			$result2= mysql_query($sql, $db);
			$row2	= mysql_fetch_array($result2);

			echo '&middot; <a href="tools/tests/results.php?tid='.$row['test_id'].SEP.'tt='.$row['title'].'">'.$row2[0].' '._AT('unmarked').'</a>';

			echo '<br />';

			/************************/
			/* Results				*/
			$sql	= "SELECT COUNT(*) FROM ".TABLE_PREFIX."tests_results WHERE test_id=$row[test_id] AND final_score<>''";
			$result2= mysql_query($sql, $db);
			$row2	= mysql_fetch_array($result2);
			echo '&middot; <a href="tools/tests/results_all.php?tid='.$row['test_id'].SEP.'tt='.$row['title'].'">'.$row2[0].' '._AT('results').'</a>';
			
			echo '<br />';

			/************************/
			/* Preview				*/
			echo '&middot; <a href="tools/tests/preview.php?tid='.$row['test_id'].SEP.'tt='.$row['title'].'">'._AT('preview').'</a>';

			echo '</small></td>';
			if (authenticate(AT_PRIV_TEST_CREATE, AT_PRIV_RETURN)) {
				echo '<td class="row1"><small>&middot; <a href="tools/tests/edit_test.php?tid='.$row['test_id'].SEP.'tt='.$row['title'].'">'._AT('edit').'</a><br />&middot; <a href="tools/tests/delete_test.php?tid='.$row['test_id'].SEP.'tt='.$row['title'].'">'._AT('delete').'</a></small></td>';
			}
			echo '</tr>';
 
			if ($count < $num_tests) {
				echo '<tr><td height="1" class="row2" colspan="'.$cols.'"></td></tr>';
			}
		} while ($row = mysql_fetch_array($result));
	} else {
		echo '<tr><td colspan="'.$cols.'" class="row1"><small><em>'._AT('no_tests').'</em></small></td></tr>';
	}

	echo '</table>';

	echo '<br />';

	require(AT_INCLUDE_PATH.'footer.inc.php');
?>