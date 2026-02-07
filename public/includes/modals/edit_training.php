<div id="editTrainingModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editTrainingModal').style.display='none'">&times;</span>
        <h2>Training bearbeiten</h2>
        <form action="action.php" method="POST" id="editTrainingForm">
            <input type="hidden" name="action" value="edit_training">
            <input type="hidden" name="training_id" id="edit_training_id">
            <div>
                <label>Datum:</label>
                <input type="date" name="training_date" id="edit_training_date" required>
            </div>
            <div>
                <label>Uhrzeit:</label>
                <input type="time" name="training_time" id="edit_training_time" required>
            </div>
            <div>
                <label>Mannschaften:</label>
                <select name="team_ids[]" id="edit_training_team_ids" multiple style="height: 100px;" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="display: block; grid-column: 2; margin-top: -10px; color: #666;">Strg halten für Mehrfachauswahl.</small>
            </div>
            <button type="submit">Änderungen speichern</button>
        </form>
    </div>
</div>