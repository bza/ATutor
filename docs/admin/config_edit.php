<?php
/************************************************************************/
/* ATutor																*/
/************************************************************************/
/* Copyright (c) 2002-2005 by Greg Gay, Joel Kronenberg & Heidi Hazelton*/
/* Adaptive Technology Resource Centre / University of Toronto			*/
/* http://atutor.ca														*/
/*																		*/
/* This program is free software. You can redistribute it and/or		*/
/* modify it under the terms of the GNU General Public License			*/
/* as published by the Free Software Foundation.						*/
/************************************************************************/
// $Id$

define('AT_INCLUDE_PATH', '../include/');
require(AT_INCLUDE_PATH.'vitals.inc.php');
admin_authenticate(AT_ADMIN_PRIV_ADMIN);

$tokens = array('{GENERATED_COMMENTS}',
			'{USER}',
			'{PASSWORD}',
			'{HOST}',
			'{PORT}',
			'{DBNAME}',
			'{TABLE_PREFIX}',
			'{CONTENT_DIR}',
			'{MAIL_USE_SMTP}',
			'{GET_FILE}',
			'{EMAIL}',
			'{EMAIL_NOTIFY}',
			'{INSTRUCTOR_REQUESTS}',			
			'{APPROVE_INSTRUCTORS}',			
			'{MAX_FILE_SIZE}',
			'{MAX_COURSE_SIZE}',
			'{MAX_COURSE_FLOAT}',
			'{ILL_EXT}',
			'{SITE_NAME}',
			'{HOME_URL}',
			'{THEME_CATEGORIES}',
			'{COURSE_BACKUPS}',
			'{EMAIL_CONFIRMATION}',
			'{MASTER_LIST}',
			'{CACHE_DIR}',
			'{DEFAULT_LANGUAGE}',
			'{AC_PATH}',
			'{AC_TABLE_PREFIX}',
			);

if (isset($_POST['cancel'])) {
	$msg->addFeedback('CANCELLED');
	header('Location: index.php');
	exit;
} else if (isset($_POST['submit'])) {
	$_POST['max_file_size'] = intval($_POST['max_file_size']);
	$_POST['max_course_size'] = intval($_POST['max_course_size']);
	$_POST['max_course_float'] = intval($_POST['max_course_float']);
	$_POST['site_name'] = trim($_POST['site_name']);
	$_POST['cache_dir'] = trim($_POST['cache_dir']);

	//check that all values have been set	
	if ($_POST['max_file_size'] < 1) {
		$msg->addError('NO_FILE_SIZE');
	}
	if (!$_POST['max_course_size']) {
		$msg->addError('NO_COURSE_SIZE');
	}
	if (!$_POST['max_course_float']) {
		$msg->addError('NO_COURSE_FLOAT');
	}
	if (!$_POST['site_name']) {
		$msg->addError('NO_SITE_NAME');
	}

	/* email check */
	if (!$_POST['email']) {
		$msg->addError('EMAIL_MISSING');
	} else if (!eregi("^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,6}$", $_POST['email'])) {
		$msg->addError('EMAIL_INVALID');	
	}


	if ($_POST['cache_dir']) {
		if (!is_dir($_POST['cache_dir'])) {
			$msg->addError('CACHE_DIR_NOT_EXIST');
		} else if (!is_writable($_POST['cache_dir'])){
			$msg->addError('CACHE_DIR_NOT_WRITEABLE');
		}
	}

	if (!$msg->containsErrors()) {
		$comments = '/*'.str_pad(' This file was generated by the ATutor '.VERSION. ' configuration script.', 70, ' ').'*/
/*'.str_pad(' File generated '.date('Y-m-d H:m:s'), 70, ' ').'*/';

		$_POST['ill_ext'] = str_replace(' ', '', $_POST['ill_ext']);
		if (!defined('AC_PATH')) {
			define('AC_PATH', '');
			define('AC_TABLE_PREFIX', '');
		}

		$values = array($comments,
						DB_USER,
						DB_PASSWORD,
						addslashes(DB_HOST),
						DB_PORT,
						DB_NAME,
						TABLE_PREFIX,
						addslashes(AT_CONTENT_DIR),
						MAIL_USE_SMTP ? 'TRUE' : 'FALSE',
						AT_FORCE_GET_FILE ? 'TRUE' : 'FALSE',
						$_POST['email'],
						$_POST['email_notification'],
						$_POST['allow_instructor_requests'],
						$_POST['auto_approve_instructors'],
						$_POST['max_file_size'],
						$_POST['max_course_size'],
						$_POST['max_course_float'],
						'\'' . str_replace(',','\',\'', $_POST['ill_ext']) . '\'' ,
						addslashes($_POST['site_name']),
						addslashes($_POST['home_url']),
						$_POST['theme_categories'],
						intval($_POST['course_backups']),
						$_POST['email_confirmation'],
						$_POST['master_list'],
						addslashes($_POST['cache_dir']),
						$_POST['language'],
						addslashes(AC_PATH),
						AC_TABLE_PREFIX);

		$config_template = file_get_contents('config_template.php');
		$config_template = str_replace($tokens, $values, $config_template);


		// copy old config file
		copy(AT_INCLUDE_PATH . 'config.inc.php', AT_CONTENT_DIR. 'config.back.inc.php');

		if (!$fp = @fopen(AT_INCLUDE_PATH . 'config.inc.php', 'wb')) {
			 return false;
		}
		@ftruncate($fp,0);
		if (!@fwrite($fp, $config_template, strlen($config_template))) {
			return false;
		}
		@fclose($fp);

		write_to_log(AT_ADMIN_LOG_UPDATE, 'config.inc.php', 1 , '');

		$msg->addFeedback(array('CONFIG_SAVED', AT_CONTENT_DIR . 'config.back.inc.php'));
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
}

$onload = 'document.form.sitename.focus();';

require(AT_INCLUDE_PATH.'header.inc.php');

$disabled = '';

@chmod(AT_INCLUDE_PATH . 'config.inc.php', 0666);

/* check if the config.inc file is writeable */
if (!is_writable(AT_INCLUDE_PATH . 'config.inc.php')) {
	$msg->addError('CONFIG_NOT_WRITEABLE');
	$msg->printErrors();
	$disabled = ' disabled="disabled"';
}

if (!isset($_POST['submit'])) {
	$defaults['site_name'] = SITE_NAME;
	$defaults['home_url']  = HOME_URL;
	$defaults['language']  = DEFAULT_LANGUAGE;
	$defaults['email']     = EMAIL;
	$defaults['email_notification'] = EMAIL_NOTIFY ? 'TRUE' : 'FALSE';
	$defaults['allow_instructor_requests'] = ALLOW_INSTRUCTOR_REQUESTS ? 'TRUE' : 'FALSE';
	$defaults['auto_approve_instructors']  = AUTO_APPROVE_INSTRUCTORS ? 'TRUE' : 'FALSE';

	$defaults['max_file_size'] = $MaxFileSize;
	$defaults['max_course_size'] = $MaxCourseSize;
	$defaults['max_course_float'] = $MaxCourseFloat;
	$defaults['ill_ext'] = implode(', ', $IllegalExtentions);

	$defaults['cache_dir'] = CACHE_DIR;
	$defaults['theme_categories'] = AT_ENABLE_CATEGORY_THEMES ? 'TRUE' : 'FALSE';

	$defaults['course_backups'] = AT_COURSE_BACKUPS;

	$defaults['email_confirmation'] = (defined('AT_EMAIL_CONFIRMATION') && AT_EMAIL_CONFIRMATION) ? 'TRUE' : 'FALSE';
	$defaults['master_list']        = (defined('AT_MASTER_LIST') && AT_MASTER_LIST) ? 'TRUE' : 'FALSE';
} else {
	$defaults = $_POST;
}
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="form">
<div class="input-form">
	<div class="row">
		<div class="required" title="<?php echo _AT('required_field'); ?>">*</div><label for="sitename"><?php echo _AT('site_name'); ?></label><br />
		<input type="text" name="site_name" size="28" maxlength="60" id="sitename" value="<?php if (!empty($_POST['site_name'])) { echo stripslashes(htmlspecialchars($_POST['site_name'])); } else { echo $defaults['site_name']; } ?>" <?php echo $disabled; ?> />
	</div>

	<div class="row">
		<label for="home_url"><?php echo _AT('home_url'); ?></label><br />

		<input type="text" name="home_url" size="28" maxlength="60" id="home_url" value="<?php if (!empty($_POST['home_url'])) { echo stripslashes(htmlspecialchars($_POST['home_url'])); } else { echo $defaults['home_url']; } ?>" <?php echo $disabled; ?> />
	</div>

	<div class="row">
		<label for="default_lang"><?php echo _AT('default_language'); ?></label><br />

		<?php if (!empty($_POST['language'])) { 
				$select_lang = $_POST['language']; 
			} else { 
				$select_lang = $defaults['language']; 
			} ?>
		<?php if ($disabled): ?>
			<select name="language" id="default_lang" disabled="disabled"><option><?php echo $select_lang; ?></option></select>
		<?php else: ?>
			<?php $languageManager->printDropdown($select_lang, 'language', 'default_lang'); ?>
		<?php endif; ?>
	</div>

	<div class="row">
		<div class="required" title="<?php echo _AT('required_field'); ?>">*</div><label for="cemail"><?php echo _AT('contact_email'); ?></label><br />
		<input type="text" name="email" id="cemail" size="30" value="<?php if (!empty($_POST['email'])) { echo stripslashes(htmlspecialchars($_POST['email'])); } else { echo $defaults['email']; } ?>" <?php echo $disabled; ?> />
	</div>

	<div class="row">
		<div class="required" title="<?php echo _AT('required_field'); ?>">*</div><label for="maxfile"><?php echo _AT('maximum_file_size'); ?></label><br />
		<input type="text" size="10" name="max_file_size" id="maxfile" value="<?php if (!empty($defaults['max_file_size'])) { echo stripslashes(htmlspecialchars($defaults['max_file_size'])); } else { echo $defaults['max_file_size']; } ?>" <?php echo $disabled; ?> /> <?php echo _AT('bytes'); ?>
	</div>

	<div class="row">
		<div class="required" title="<?php echo _AT('required_field'); ?>">*</div><label for="maxcourse"><?php echo _AT('maximum_course_size'); ?></label><br />
		<input type="text" size="10" name="max_course_size" id="maxcourse" value="<?php if (!empty($defaults['max_course_size'])) { echo stripslashes(htmlspecialchars($defaults['max_course_size'])); } else { echo $defaults['max_course_size']; } ?>" <?php echo $disabled; ?> /> <?php echo _AT('bytes'); ?>
	</div>

	<div class="row">
		<div class="required" title="<?php echo _AT('required_field'); ?>">*</div><label for="float"><?php echo _AT('maximum_course_float'); ?></label><br />
		<input type="text" size="10" name="max_course_float" id="float" value="<?php if (!empty($defaults['max_course_float'])) { echo stripslashes(htmlspecialchars($defaults['max_course_float'])); } else { echo $defaults['max_course_float']; } ?>" <?php echo $disabled; ?> /> <?php echo _AT('bytes'); ?>
	</div>

	<div class="row">
		<?php echo _AT('master_list_authentication'); ?><br />

		<input type="radio" name="master_list" value="TRUE" id="ml_y" <?php if ($defaults['master_list']=='TRUE' || empty($defaults['master_list'])) { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="ml_y"><?php echo _AT('enable'); ?></label> <input type="radio" name="master_list" value="FALSE" id="ml_n" <?php if($defaults['master_list']=='FALSE') { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="ml_n"><?php echo _AT('disable'); ?></label>
	</div>

	<div class="row">
		<?php echo _AT('require_email_confirmation'); ?><br />

		<input type="radio" name="email_confirmation" value="TRUE" id="ec_y" <?php if ($defaults['email_confirmation']=='TRUE' || empty($defaults['email_confirmation'])) { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="ec_y"><?php echo _AT('enable'); ?></label> <input type="radio" name="email_confirmation" value="FALSE" id="ec_n" <?php if($defaults['email_confirmation']=='FALSE') { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="ec_n"><?php echo _AT('disable'); ?></label>
	</div>
		
	<div class="row">
		<?php echo _AT('allow_instructor_requests'); ?><br />

		<input type="radio" name="allow_instructor_requests" value="TRUE" id="air_y" <?php if($defaults['allow_instructor_requests']=='TRUE' || empty($defaults['allow_instructor_requests'])) { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="air_y"><?php echo _AT('enable'); ?></label> <input type="radio" name="allow_instructor_requests" value="FALSE" id="air_n" <?php if($defaults['allow_instructor_requests']=='FALSE') { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="air_n"><?php echo _AT('disable'); ?></label>
	</div>

	<div class="row">
		<?php echo _AT('instructor_request_email_notification'); ?><br />

		<input type="radio" name="email_notification" value="TRUE" id="en_y" <?php if ($defaults['email_notification']=='TRUE' || empty($defaults['email_notification'])) { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="en_y"><?php echo _AT('enable'); ?></label> <input type="radio" name="email_notification" value="FALSE" id="en_n" <?php if($defaults['email_notification']=='FALSE') { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="en_n"><?php echo _AT('disable'); ?></label>
	</div>

	<div class="row">
		<?php echo _AT('auto_approve_instructors'); ?><br />
		
		<input type="radio" name="auto_approve_instructors" value="TRUE" id="aai_y" <?php if($defaults['auto_approve_instructors']=='TRUE') { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="aai_y"><?php echo _AT('enable'); ?></label> <input type="radio" name="auto_approve_instructors" value="FALSE" id="aai_n" <?php if($defaults['auto_approve_instructors']=='FALSE' || empty($defaults['auto_approve_instructors'])) { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="aai_n"><?php echo _AT('disable'); ?></label>
	</div>

	<div class="row">
		<?php echo _AT('theme_specific_categories'); ?><br />
		<input type="radio" name="theme_categories" value="TRUE" id="tc_y" <?php if($defaults['theme_categories']=='TRUE') { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="tc_y"><?php echo _AT('enable'); ?></label> <input type="radio" name="theme_categories" value="FALSE" id="tc_n" <?php if($defaults['theme_categories']=='FALSE' || empty($defaults['theme_categories'])) { echo 'checked="checked"'; }?> <?php echo $disabled; ?> /><label for="tc_n"><?php echo _AT('disable'); ?></label>
	</div>



	<div class="row">
		<label for="ext"><?php echo _AT('illegal_file_extensions'); ?></label><br />
		<textarea name="ill_ext" cols="24" id="ext" rows="2" class="formfield" <?php echo $disabled; ?>><?php if (!empty($defaults['ill_ext'])) { echo $defaults['ill_ext']; } else { echo $defaults['ill_ext']; } ?></textarea>
	</div>

	<div class="row">
		<label for="cache"><?php echo _AT('cache_directory'); ?></label><br />
		<input type="text" name="cache_dir" id="cache" size="40" value="<?php if (!empty($_POST['cache_dir'])) { echo stripslashes(htmlspecialchars($_POST['cache_dir'])); } else { echo $defaults['cache_dir']; } ?>" <?php echo $disabled; ?> />
	</div>

	<div class="row">
		<label for="course_backups"><?php echo _AT('course_backups'); ?></label><br />
		<input type="text" size="2" name="course_backups" id="course_backups" value="<?php if (!empty($_POST['course_backups'])) { echo stripslashes(htmlspecialchars($_POST['course_backups'])); } else { echo $defaults['course_backups']; } ?>" <?php echo $disabled; ?> />
	</div>

	<div class="row buttons">
		<input type="submit" name="submit" value="<?php echo _AT('save'); ?>" accesskey="s" <?php echo $disabled; ?> />
		<input type="submit" name="cancel" value="<?php echo _AT('cancel'); ?>" <?php echo $disabled; ?> />
	</div>
</div>
</form>

<?php require(AT_INCLUDE_PATH.'footer.inc.php'); ?>