<style>
    .message-box { width: 50%; }
</style>
<div class="box table-list-wrap">
    <div class="tbl-ctrls">
        <?=form_open($form_url, 'class="settings"')?>
        <h1><?=$cp_heading?></h1>
        <?=ee('CP/Alert')->getAllInlines()?>
        <div class="tbl-wrap">
            <?php if (empty($rows)): ?>
            <table cellspacing="0" class="empty no-results">
                <tr>
                    <td>
                        <?=lang($no_results['text'])?>
                        <?php if ( ! empty($no_results['action_text'])): ?>
                            <a class="btn action" <?=$no_results['external'] ? 'rel="external"' : '' ?> href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>>
                        <?php endif ?>
                    </td>
                </tr>
            </table>
            <?php else: ?>
            <table cellspacing="0">
                <thead>
                <tr>
                    <th><?=lang('form');?></th>
                    <th><?=lang('notify');?></th>
                    <th><?=lang('message');?></th>
                    <th><?=lang('enabled');?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $id => $row): ?>
                    <tr<?php if ($row['enabled'] == 'y'): ?> class="selected"<?php endif ?>/>
                        <td><?=$row['name']?></td>
                        <td><?=form_textarea('rows['.$id.'][notify_email]', $row['notify_email'])?></td>
                        <td class="message-box"><?=form_textarea('rows['.$id.'][notify_message]', $row['notify_message'])?></td>
                        <td><?=form_checkbox('rows['.$id.'][enabled]', 'y', ($row['enabled'] == 'y' ? true : false))?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php endif ?>
        </div>

        <?php if ( !empty($rows)): ?>
        <fieldset class="form-ctrls">
            <button class="btn submit"><?=lang('submit')?></button>
        </fieldset>
        <?php endif ?>
        <?=form_close()?>
    </div>
</div>