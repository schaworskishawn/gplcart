<?php
/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
?>
<?php if (!empty($records) || $_filtering) { ?>
<div class="panel panel-default">
  <div class="panel-heading clearfix">
    <div class="btn-toolbar pull-right">
      <a class="btn btn-default" href="<?php echo $this->url('', array('clear' => true, 'token' => $_token)); ?>">
        <?php echo $this->text('Clear'); ?>
      </a>
    </div>
  </div>
  <div class="panel-body table-responsive">
    <table class="table table-condensed report-events">
      <thead>
        <tr>
          <th><a href="<?php echo $sort_text; ?>"><?php echo $this->text('Message'); ?> <i class="fa fa-sort"></i></a></th>
          <th><a href="<?php echo $sort_type; ?>"><?php echo $this->text('Type'); ?> <i class="fa fa-sort"></i></a></th>
          <th><a href="<?php echo $sort_severity; ?>"><?php echo $this->text('Severity'); ?> <i class="fa fa-sort"></i></a></th>
          <th><a href="<?php echo $sort_time; ?>"><?php echo $this->text('Created'); ?> <i class="fa fa-sort"></i></a></th>
          <th></th>
        </tr>
        <tr class="filters active">
          <th>
            <input class="form-control" name="text" value="<?php echo $filter_text; ?>" placeholder="<?php echo $this->text('Any'); ?>">
          </th>
          <th>
            <select name="type" class="form-control">
              <option value="any"><?php echo $this->text('Any'); ?></option>
              <?php foreach ($types as $type) { ?>
              <option value="<?php echo $type; ?>"<?php echo $type == $filter_type ? ' selected' : ''; ?>>
              <?php echo $type; ?>
              </option>
              <?php } ?>
            </select>
          </th>
          <th>
            <select name="severity" class="form-control">
              <option value="any"><?php echo $this->text('Any'); ?></option>
              <?php foreach ($severities as $severity => $severity_name) { ?>
              <option value="<?php echo $severity; ?>"<?php echo $severity == $filter_severity ? ' selected' : ''; ?>>
              <?php echo $severity_name; ?>
              </option>
              <?php } ?>
            </select>
          </th>
          <th></th>
          <th>
            <button type="button" class="btn btn-default clear-filter" title="<?php echo $this->text('Reset filter'); ?>">
              <i class="fa fa-refresh"></i>
            </button>
            <button type="button" class="btn btn-default filter" title="<?php echo $this->text('Filter'); ?>">
              <i class="fa fa-search"></i>
            </button>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if ($_filtering && empty($records)) { ?>
        <tr>
          <td class="middle" colspan="5">
            <?php echo $this->text('No results'); ?>
            <a href="#" class="clear-filter"><?php echo $this->text('Reset'); ?></a>
          </td>
        </tr>
        <?php } ?>
        <?php foreach ($records as $record) { ?>
        <tr>
          <td>
            <a href="#" onclick="return false;" data-toggle="collapse" data-target="#message-<?php echo $record['log_id']; ?>">
              <?php echo $this->e(strip_tags($record['summary'])); ?>
            </a>
          </td>
          <td><?php echo $this->e($record['type']); ?></td>
          <td>
            <span class="label label-<?php echo $record['severity']; ?>">
              <?php echo $this->e($record['severity_text']); ?>
            </span>
          </td>
          <td><?php echo $record['time']; ?></td>
          <td></td>
        </tr>
        <tr class="collapse active" id="message-<?php echo $record['log_id']; ?>">
          <td colspan="5">
            <ul class="list-unstyled">
              <li><b><?php echo $this->text('Message'); ?></b> : <?php echo $this->filter($record['text']); ?></li>
              <?php if (!empty($record['data']['file'])) { ?>
              <li><b><?php echo $this->text('File'); ?></b> : <?php echo $this->e($record['data']['file']); ?></li>
              <?php } ?>
              <?php if (!empty($record['data']['line'])) { ?>
              <li><b><?php echo $this->text('Line'); ?></b> : <?php echo $this->e($record['data']['line']); ?></li>
              <?php } ?>
              <?php if (!empty($record['data']['code'])) { ?>
              <li><b><?php echo $this->text('Code'); ?></b> : <?php echo $this->e($record['data']['code']); ?></li>
              <?php } ?>
            </ul>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    <?php if(!empty($_pager)) { ?>
    <?php echo $_pager; ?>
    <?php } ?>
  </div>
</div>
<?php } else { ?>
<div class="row">
  <div class="col-md-12">
    <?php echo $this->text('You have no recorded events yet'); ?>
  </div>
</div>
<?php } ?>