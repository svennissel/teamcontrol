<div id="editTeamModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editTeamModal').style.display='none'">&times;</span>
        <h2>Mannschaft bearbeiten</h2>
        <form action="action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_team">
            <input type="hidden" name="team_id" id="edit_team_id">
            <div>
                <label>Name:</label>
                <input type="text" name="name" id="edit_team_name" required>
            </div>
            <div>
                <label>Logo (optional):</label>
                <input type="file" name="logo" accept="image/*">
            </div>
            <button type="submit">Änderungen speichern</button>
        </form>
    </div>
</div>