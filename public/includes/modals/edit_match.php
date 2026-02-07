<div id="editMatchModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editMatchModal').style.display='none'">&times;</span>
        <h2>Spiel bearbeiten</h2>
        <form action="action.php" method="POST" id="editMatchForm">
            <input type="hidden" name="action" value="edit_match">
            <input type="hidden" name="match_id" id="edit_match_id">
            <div>
                <label>Datum:</label>
                <input type="date" name="match_date" id="edit_match_date" required>
            </div>
            <div>
                <label>Startzeit:</label>
                <input type="time" name="start_time" id="edit_match_start_time" required>
            </div>
            <div>
                <label>Treffen:</label>
                <input type="time" name="meeting_time" id="edit_match_meeting_time">
            </div>
            <div>
                <label>Gegner:</label>
                <input type="text" name="opponent" id="edit_match_opponent" required>
            </div>
            <div>
                <label>Heimspiel:</label>
                <input type="checkbox" name="is_home_game" id="edit_match_is_home">
            </div>
            <div id="edit_location_container">
                <label>Anschrift (für Auswärtsspiele):</label>
                <input type="text" name="location" id="edit_match_location" placeholder="Straße, PLZ Ort">
            </div>
            <div>
                <label>Mannschaft:</label>
                <select name="team_id" id="edit_match_team_id" required>
                    <option value="">-- Mannschaft wählen --</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Änderungen speichern</button>
        </form>
    </div>
</div>