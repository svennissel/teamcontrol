<div id="addTeamModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addTeamModal').style.display='none'">&times;</span>
        <h2>Neue Mannschaft</h2>
        <form action="action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_team">
            <div>
                <label>Name:</label>
                <input type="text" name="name" required>
            </div>
            <div>
                <label>Logo:</label>
                <input type="file" name="logo" accept="image/*">
            </div>
            <button type="submit">Mannschaft anlegen</button>
        </form>
    </div>
</div>