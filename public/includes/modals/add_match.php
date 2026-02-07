<div id="addMatchModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addMatchModal').style.display='none'">&times;</span>
        <h2>Neues Spiel</h2>
        <form action="action.php" method="POST">
            <input type="hidden" name="action" value="add_match">
            <div>
                <label>Datum:</label>
                <input type="date" name="match_date" required>
            </div>
            <div>
                <label>Startzeit:</label>
                <input type="time" name="start_time" required>
            </div>
            <div>
                <label>Treffen:</label>
                <input type="time" name="meeting_time">
            </div>
            <div>
                <label>Gegner:</label>
                <input type="text" name="opponent" required>
            </div>
            <div>
                <label>Heimspiel:</label>
                <input type="checkbox" name="is_home_game" id="add_match_is_home">
            </div>
            <div id="add_location_container">
                <label>Anschrift (für Auswärtsspiele):</label>
                <input type="text" name="location" placeholder="Straße, PLZ Ort">
            </div>
            <div>
                <label>Mannschaft:</label>
                <select name="team_id" required>
                    <option value="">-- Mannschaft wählen --</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Spiel anlegen</button>
        </form>
    </div>
</div>