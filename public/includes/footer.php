    </main>
    
    <?php 
    require_once __DIR__ . '/modal_functions.php';
    
    renderAttendanceModal();

    if (isClubAdmin() || isAnyTeamAdmin($player_id_footer ?? 0)) {
        renderAddPlayerModal($teams ?? [], $player_id_footer ?? 0);
        renderEditPlayerModal($teams ?? [], $player_id_footer ?? 0);
        renderAddMatchModal($teams ?? []);
        renderAddTrainingModal($teams ?? []);
        renderEditMatchModal($teams ?? []);
        renderEditTrainingModal($teams ?? []);
    }
    
    if (isClubAdmin()) {
        renderAddTeamModal();
        renderEditTeamModal();
    }
    ?>

    <script>
        function showAttendance(attendance, title) {
            document.getElementById('attendanceModalTitle').innerText = title;
            const lists = {
                yes: document.getElementById('list-yes'),
                no: document.getElementById('list-no'),
                maybe: document.getElementById('list-maybe'),
                none: document.getElementById('list-none')
            };
            Object.values(lists).forEach(list => list.innerHTML = '');
            attendance.forEach(entry => {
                const li = document.createElement('li');
                li.textContent = entry.name;
                if (lists[entry.status]) lists[entry.status].appendChild(li);
            });
            Object.keys(lists).forEach(status => {
                if (lists[status].children.length === 0) {
                    const li = document.createElement('li');
                    li.textContent = 'Keine Einträge';
                    li.classList.add('empty-list');
                    lists[status].appendChild(li);
                }
            });
            document.getElementById('attendanceModal').style.display = 'block';
        }

        async function handleVote(event) {
            event.preventDefault();
            const form = event.currentTarget;
            const formData = new FormData(form);
            
            // Zuverlässigere Methode um den Status zu bekommen, falls event.submitter nicht existiert
            let status = event.submitter ? event.submitter.value : null;
            
            // Falls handleVote anders aufgerufen wurde (nicht über submitter), suchen wir den aktiven Button
            if (!status) {
                // Das ist ein Fallback, falls der Browser event.submitter nicht unterstützt
                return; // Ohne Status können wir nichts tun
            }
            
            formData.append('status', status);
               try {
                 const response = await fetch('action.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        console.info('Vote successful: ', JSON.stringify(data));
                        const buttons = form.querySelectorAll('button[type="submit"]');
                        buttons.forEach(btn => {
                            if (btn.value === status) btn.classList.add('active');
                            else btn.classList.remove('active');
                            if (data.counts[btn.value] !== undefined) {
                                const countSpan = btn.querySelector('.count');
                                if (countSpan) countSpan.textContent = data.counts[btn.value];
                            }
                        });
                        const voteButtonsDiv = form.closest('.vote-buttons');
                        const attendanceBtn = voteButtonsDiv ? voteButtonsDiv.querySelector('.btn-attendance') : null;
                        if (attendanceBtn) {
                            const originalOnclick = attendanceBtn.getAttribute('onclick');
                            if (originalOnclick) {
                                const titleMatch = originalOnclick.match(/,\s*"([^"]+)"\)/);
                                const title = titleMatch ? titleMatch[1] : 'Teilnehmerliste';
                                attendanceBtn.setAttribute('onclick', `showAttendance(${JSON.stringify(data.attendance)}, "${title}")`);
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error voting:' , error);
                // Im Fehlerfall laden wir die Seite neu als Fallback, falls das Problem persistent ist
                // Aber der User will eigentlich KEINE Weiterleitung.
                // form.submit(); // Wir entfernen das automatische Absenden im Fehlerfall um die Weiterleitung zu vermeiden
            }
        }

        function toggleLocation(checkboxId, containerId) {
            const checkbox = document.getElementById(checkboxId);
            const container = document.getElementById(containerId);
            if (checkbox && container) {
                container.style.display = checkbox.checked ? 'none' : 'block';
            }
        }

        function filterEvents(teamClass, btn) {
            // Filter Buttons Status aktualisieren
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Event-Cards filtern
            const cards = document.querySelectorAll('.event-card');
            cards.forEach(card => {
                if (teamClass === 'all') {
                    card.style.display = 'block';
                } else {
                    if (card.classList.contains(teamClass)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addHomeCheckbox = document.getElementById('add_match_is_home');
            if (addHomeCheckbox) {
                addHomeCheckbox.addEventListener('change', function() {
                    toggleLocation('add_match_is_home', 'add_location_container');
                });
                toggleLocation('add_match_is_home', 'add_location_container');
            }
            const editHomeCheckbox = document.getElementById('edit_match_is_home');
            if (editHomeCheckbox) {
                editHomeCheckbox.addEventListener('change', function() {
                    toggleLocation('edit_match_is_home', 'edit_location_container');
                });
            }
        });

        function editMatch(match) {
            document.getElementById('edit_match_id').value = match.id;
            document.getElementById('edit_match_date').value = match.match_date;
            document.getElementById('edit_match_start_time').value = match.start_time;
            document.getElementById('edit_match_meeting_time').value = match.meeting_time;
            document.getElementById('edit_match_opponent').value = match.opponent;
            document.getElementById('edit_match_is_home').checked = match.is_home_game === 1;
            document.getElementById('edit_match_location').value = match.location;
            document.getElementById('edit_match_team_id').value = match.team_id;
            toggleLocation('edit_match_is_home', 'edit_location_container');
            document.getElementById('editMatchModal').style.display = 'block';
        }

        function editTraining(training) {
            document.getElementById('edit_training_id').value = training.id;
            document.getElementById('edit_training_date').value = training.training_date;
            document.getElementById('edit_training_time').value = training.training_time;
            const teamSelect = document.getElementById('edit_training_team_ids');
            Array.from(teamSelect.options).forEach(option => {
                option.selected = training.teams && training.teams.includes(parseInt(option.value));
            });
            document.getElementById('editTrainingModal').style.display = 'block';
        }

        function editTeam(team) {
            document.getElementById('edit_team_id').value = team.id;
            document.getElementById('edit_team_name').value = team.name;
            document.getElementById('editTeamModal').style.display = 'block';
        }

        function editPlayer(player) {
            document.getElementById('edit_player_id').value = player.id;
            document.getElementById('edit_player_name').value = player.name;
            const isClubAdminCheckbox = document.getElementById('edit_player_is_club_admin');
            if (isClubAdminCheckbox) isClubAdminCheckbox.checked = player.is_club_admin == 1;
            const teamSelect = document.getElementById('edit_player_team_ids');
            Array.from(teamSelect.options).forEach(option => {
                option.selected = player.team_ids && player.team_ids.includes(parseInt(option.value));
            });
            const adminTeamSelect = document.getElementById('edit_player_admin_team_ids');
            if (adminTeamSelect) {
                Array.from(adminTeamSelect.options).forEach(option => {
                    option.selected = player.admin_team_ids && player.admin_team_ids.includes(parseInt(option.value));
                });
            }
            document.getElementById('editPlayerModal').style.display = 'block';
        }

        function toggleTrainingType(type) {
            const singleFields = document.getElementById('single_training_fields');
            const weeklyFields = document.getElementById('weekly_training_fields');
            const trainingDateInput = singleFields.querySelector('input[name="training_date"]');
            if (type === 'single') {
                singleFields.style.display = 'block';
                weeklyFields.style.display = 'none';
                trainingDateInput.required = true;
            } else {
                singleFields.style.display = 'none';
                weeklyFields.style.display = 'block';
                trainingDateInput.required = false;
            }
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        }

        function copyToClipboard(elementId) {
            const copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(() => {
                const btn = copyText.nextElementSibling;
                const originalTitle = btn.title;
                btn.innerText = '✓';
                btn.title = 'Kopiert!';
                setTimeout(() => {
                    btn.innerText = '📋';
                    btn.title = originalTitle;
                }, 2000);
            });
        }
    </script>
</body>
</html>
