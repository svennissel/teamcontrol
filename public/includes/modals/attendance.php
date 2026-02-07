<div id="attendanceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('attendanceModal').style.display='none'">&times;</span>
        <h2 id="attendanceModalTitle">Teilnehmerliste</h2>
        <div id="attendanceContent">
            <div class="attendance-group">
                <h4>👍 Zugesagt</h4>
                <ul id="list-yes"></ul>
            </div>
            <div class="attendance-group">
                <h4>👎 Abgesagt</h4>
                <ul id="list-no"></ul>
            </div>
            <div class="attendance-group">
                <h4>❓ Vielleicht</h4>
                <ul id="list-maybe"></ul>
            </div>
            <div class="attendance-group">
                <h4>⚪ Noch offen</h4>
                <ul id="list-none"></ul>
            </div>
        </div>
    </div>
</div>