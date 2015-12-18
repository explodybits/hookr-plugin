<?php !defined('ABSPATH') && exit; ?>
<div class="wrap">
    <header>
        <h1><a href="http://hookr.io"><img src="<?php echo plugins_url('/assets/images/hookr-blue.svg', HOOKR_PLUGIN_FILE) ?>" /></a><small><?php echo $title ?></small></h1>    
    </header>
    <form action="options.php" method="post">
        <?php $this->settings_fields() ?>
        <fieldset id="<?php $this->field_id('global') ?>">
            <legend>Global</legend>
            <table class="form-table">
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <?php $this->render('fields/enable', $data); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Activate</th>
                    <td>
                        <ul>
                            <li class="disabled"><input type="checkbox" name="<?php $this->field_name('hookr') ?>" id="<?php $this->field_id('hookr') ?>" value="1" <?php checked(@$settings->hookr, '1') ?> disabled="disabled"/><label for="<?php $this->field_id('hookr') ?>">Beastmode (feature disabled)</label><br /><small>Connect to hookr.io for additional hook details.</small></li>                            
                       </ul>
                    </td>
                </tr>                
            </table>                
        </fieldset>
        <?php submit_button() ?>
        <?php $this->render('settings-section', array_merge($data, array('section' => 'public'))); ?>
        <?php $this->render('settings-section', array_merge($data, array('section' => 'admin'))); ?>
    </form>
</div>