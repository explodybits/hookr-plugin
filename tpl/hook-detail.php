<?php !defined('ABSPATH') && exit; ?>
<?php if (@isset($hook)): ?>
<dl data-hookr="true">    
    <dt>Tag</dt>
    <dd><?php echo $hook->tag ?></dd>
    <?php if (null !== ($annot = $hook->annotation)): ?>
    <?php if (@isset($hook->caller)): ?>
    <dt>Invoker</dt>
    <dd>
        <p><strong><?php echo $hook->caller->file ?></strong> (<?php echo $hook->caller->line ?>)</p>
        <pre><?php echo trim($hook->caller->invoker) ?></pre>
    </dd>
    <?php endif; ?>
    <dt>Description</dt>
    <dd><p><?php echo $annot->get_desc_full() ?></p></dd>
    <?php endif; ?>
    <dt>Value</dt>
    <dd><pre><?php echo $hook->value ?></pre></dd>
</dl>
<?php else: ?>
<p>Cache Expired. Try reloading the page.</p>
<?php endif; ?>
