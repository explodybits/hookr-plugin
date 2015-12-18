<?php !defined('ABSPATH') && exit; ?>
        <fieldset id="<?php $this->field_id($section) ?>">
            <legend><?php echo ucfirst($section) ?></legend>
            <table class="form-table">
                <tr>
                    <th scope="row">Activate</th>
                    <td>
                        <ul>
                            <li><input type="checkbox" name="<?php $this->field_name(sprintf('%s_actions', $section)) ?>" id="<?php $this->field_id(sprintf('%s-actions', $section)) ?>" value="1" <?php checked($this->get_setting(sprintf('%s_actions', $section)), '1') ?> /><label for="<?php $this->field_id(sprintf('%s-actions', $section)) ?>">Actions</label></li>
                            <li><input type="checkbox" name="<?php $this->field_name(sprintf('%s_filters', $section)) ?>" id="<?php $this->field_id(sprintf('%s-filters', $section)) ?>" value="1" <?php checked($this->get_setting(sprintf('%s_filters', $section)), '1') ?> /><label for="<?php $this->field_id(sprintf('%s-filters', $section)) ?>">Filters</label></li>                            
                            <li><input type="checkbox" name="<?php $this->field_name(sprintf('%s_off_canvas', $section)) ?>" id="<?php $this->field_id(sprintf('%s-off-canvas', $section)) ?>" value="1" <?php checked($this->get_setting(sprintf('%s_off_canvas', $section)), '1') ?> /><label for="<?php $this->field_id(sprintf('%s-off-canvas', $section)) ?>">Hidden (Off-Canvas)</label><br /><small>These hooks trigger before or after theme has rendered and are normally &quot;hidden.&quot;</small></li>
                            <li><input type="checkbox" name="<?php $this->field_name(sprintf('%s_trace', $section)) ?>" id="<?php $this->field_id(sprintf('%s-trace', $section)) ?>" value="1" <?php checked($this->get_setting(sprintf('%s_trace', $section)), '1') ?>/><label for="<?php $this->field_id(sprintf('%s_trace', $section)) ?>">Backtrace</label><br /><small>Track file &amp; line the hook was invoked from. <strong>Enabling may degrade performance by ~20%.</strong></small></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Context</th>
                    <td>
                        <ul>
                            <li><input type="checkbox" name="<?php $this->field_name(sprintf('%s_ctx_header', $section)) ?>" id="<?php $this->field_id(sprintf('%s-ctx-header', $section)) ?>" value="<?php echo HOOKR_CONTEXT_HEADER ?>" <?php checked($this->get_setting(sprintf('%s_ctx_header', $section)), HOOKR_CONTEXT_HEADER) ?> /><label for="<?php $this->field_id(sprintf('%s-ctx-header', $section)) ?>">Header</li>                            
                            <li><input type="checkbox" name="<?php $this->field_name(sprintf('%s_ctx_middle', $section)) ?>" id="<?php $this->field_id(sprintf('%s-ctx-middle', $section)) ?>" value="<?php echo HOOKR_CONTEXT_MIDDLE ?>" <?php checked($this->get_setting(sprintf('%s_ctx_middle', $section)), HOOKR_CONTEXT_MIDDLE) ?> /><label for="<?php $this->field_id(sprintf('%s-ctx-middle', $section)) ?>">Middle</li>
                            <li><input type="checkbox" name="<?php $this->field_name(sprintf('%s_ctx_footer', $section)) ?>" id="<?php $this->field_id(sprintf('%s-ctx-footer', $section)) ?>" value="<?php echo HOOKR_CONTEXT_FOOTER ?>" <?php checked($this->get_setting(sprintf('%s_ctx_footer', $section)), HOOKR_CONTEXT_FOOTER) ?> /><label for="<?php $this->field_id(sprintf('%s-ctx-footer', $section)) ?>">Footer</li>                            
                            <li><input type="checkbox" name="<?php $this->field_name(sprintf('%s_ctx_hidden', $section)) ?>" id="<?php $this->field_id(sprintf('%s-ctx-hidden', $section)) ?>" value="<?php echo HOOKR_CONTEXT_HIDDEN ?>" <?php checked($this->get_setting(sprintf('%s_ctx_hidden', $section)), HOOKR_CONTEXT_HIDDEN) ?> /><label for="<?php $this->field_id(sprintf('%s-ctx-hidden', $section)) ?>">Hidden (Off-Canvas)</li>                            
                        </ul>
                    </td>
                </tr>                
                <tr>
                    <th scope="row">
                        <label for="<?php $this->field_id(sprintf('%s-ignore', $section)) ?>">Ignore Hooks</label><br />
                        <small>Prevent tracking of &quot;common&quot; hooks, such as <code>gettext</code> or <code>esc_html</code>.</small>
                    </th>
                    <td>
                        <textarea name="<?php $this->field_name(sprintf('%s_ignore', $section)) ?>" id="<?php $this->field_id(sprintf('%s-ignore', $section)) ?>" rows="10" cols="22" placeholder="Separate tags with [RETURN]"><?php echo $this->get_setting(sprintf('%s_ignore', $section)) ?></textarea>
                    </td>
                </tr>                
                <tr>
                    <th scope="row">
                        <label for="<?php $this->field_id(sprintf('%s-watch', $section)) ?>">Watch Hooks</label><br />
                        <small>Track specific hooks. <em>Leave blank to track everything.</em></small>
                    </th>
                    <td>
                        <textarea name="<?php $this->field_name(sprintf('%s_watch', $section)) ?>" id="<?php $this->field_id(sprintf('%s-watch', $section)) ?>" rows="10" cols="22" placeholder="Separate tags with [RETURN]"><?php echo $this->get_setting(sprintf('%s_watch', $section)) ?></textarea>
                    </td>
                </tr>                                
            </table>
            <?php submit_button() ?>            
        </fieldset>