<?php
require("../config/connection.php");
?>

<div id="syncModal" class="sync-modal">

  <div class="sync-modal-content">

    <div class="sync-modal-header">
      <h5>
        <i class="bi bi-gear-fill"></i> Sync Manager
      </h5>
      <button class="icon-btn" id="closeSyncModal">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="sync-modal-body">

      <div class="form-group">
        <label><i class="bi bi-list-task"></i> Sync Type</label>
        <select id="syncTask" class="form-control">
          <option value="">Choose action</option>
          <option value="series">Sync Series</option>
          <option value="episode">Sync Episode Meta</option>
        </select>
      </div>

      <div class="form-group">
        <label><i class="bi bi-collection-play"></i> Series</label>
        <select id="syncSlug" class="form-control">
          <option value="">Choose series</option>
          <?php
          $res = $con->query("SELECT slug,title FROM series ORDER BY title");
          while ($r = $res->fetch_assoc()) {
              echo "<option value='{$r['slug']}'>{$r['title']}</option>";
          }
          ?>
        </select>
      </div>

      <label><i class="bi bi-terminal"></i> Progress</label>
      <div id="syncLog">Waiting...</div>

    </div>

    <div class="sync-modal-footer">
      <button class="btn btn-success" id="runSyncBtn">
        <i class="bi bi-play-fill"></i> Run
      </button>
      <button class="btn btn-secondary" id="closeSyncModalFooter">
        Cancel
      </button>
    </div>

  </div>
</div>