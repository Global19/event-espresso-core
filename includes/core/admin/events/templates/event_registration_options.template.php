<p>
	<label><?php _e('Event is Active', 'event_espresso'); ?></label>
	<?php echo $is_active_select ?>
</p>

<p>
	<label for="reg-limit">
		<?php _e('Attendee Limit: ', 'event_espresso'); ?>
	</label>
	<input id="reg-limit" name="reg_limit"  size="10" type="text" value="<?php echo $_event->reg_limit(); ?>" /><br />
	<span>(<?php _e('leave blank for unlimited', 'event_espresso'); ?>)</span>
</p>

<p class="clearfix" style="clear: both;">
	<label for="group-reg"><?php _e('Allow group registrations? ', 'event_espresso'); ?></label>
	<?php echo $allow_group_reg_select; ?>
</p>

<p>
	<label for="max-registrants"><?php _e('Max Group Registrants: ', 'event_espresso'); ?></label>
	<input type="text" id="max-registrants" name="additional_limit" value="<?php echo $_event->additional_limit(); ?>" size="4" />
</p>

<?php echo $additional_registration_options; ?>

<p>
	<label><?php _e('Default Payment Status', 'event_espresso'); ?></label>
	<?php echo $default_registration_status; ?>
</p>

<p>
	<label><?php _e('Display Description', 'event_espresso'); ?></label>
	<?php echo $display_description; ?>
</p>

<p>
	<label><?php _e('Display Registration Form', 'event_espresso'); ?></label>
	<?php echo $display_registration_form; ?>
</p>

<p>
	<label><?php _e('Alternate Registration Page', 'event_espresso'); ?></label>
	<input name="externalURL" size="20" type="text" value="<?php echo $_event->external_url(); ?>">
</p>