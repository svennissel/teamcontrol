<div id="addTrainingModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addTrainingModal').style.display='none'">&times;</span>
        <h2>Neues Training</h2>
        <form action="action.php" method="POST">
            <input type="hidden" name="action" value="add_training">
            
            <div style="margin-bottom: 20px;">
                <label style="display: inline-block; margin-right: 20px;">
                    <input type="radio" name="training_type" value="single" checked onclick="toggleTrainingType('single')"> Einzeltraining
                </label>
                <label style="display: inline-block;">
                    <input type="radio" name="training_type" value="weekly" onclick="toggleTrainingType('weekly')"> Wöchentliches Training
                </label>
            </div>
            
            <div id="single_training_fields">
                <div>
                    <label>Datum:</label>
                    <input type="date" name="training_date" required>
                </div>
            </div>

            <div id="weekly_training_fields" style="display: none;">
                <div>
                    <label>Wochentag:</label>
                    <select name="day_of_week">
                        <option value="1">Montag</option>
                        <option value="2">Dienstag</option>
                        <option value="3">Mittwoch</option>
                        <option value="4">Donnerstag</option>
                        <option value="5">Freitag</option>
                        <option value="6">Samstag</option>
                        <option value="0">Sonntag</option>
                    </select>
                </div>
                <div>
                    <label>Startdatum:</label>
                    <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div>
                <label>Uhrzeit:</label>
                <input type="time" name="training_time" required>
            </div>
            <div>
                <label>Mannschaften:</label>
                <select name="team_ids[]" multiple style="height: 100px;" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="display: block; grid-column: 2; margin-top: -10px; color: #666;">Strg halten für Mehrfachauswahl.</small>
            </div>
            <button type="submit">Training anlegen</button>
        </form>
    </div>
</div>