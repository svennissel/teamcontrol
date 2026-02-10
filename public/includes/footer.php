    </main>
    
    <?php 
    require_once __DIR__ . '/modal_functions.php';
    
    renderAttendanceModal();

    if (isClubAdmin() || isAnyTeamAdmin($player_id ?? 0)) {
        renderAddPlayerModal($teams ?? [], $player_id ?? 0);
        renderEditPlayerModal($teams ?? [], $player_id ?? 0);
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

    <div id="voteTargetMenu" class="vote-target-menu" aria-hidden="true"></div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                history.pushState({ modalId: modalId }, "");
            }
        }

        function closeModal(modalId, fromHistory = false) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                if (!fromHistory && history.state && history.state.modalId === modalId) {
                    history.back();
                }
            }
        }

        window.onpopstate = function(event) {
            // Alle Modale schließen
            document.querySelectorAll('.modal').forEach(modal => {
                closeModal(modal.id, true);
            });
        };

        function showAttendance(attendance, title) {
            document.getElementById('attendanceModalTitle').innerText = title;
            const lists = {
                yes: document.getElementById('list-yes'),
                maybe: document.getElementById('list-maybe'),
                no: document.getElementById('list-no'),
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
            openModal('attendanceModal');
        }

        async function performVote(form, status) {
            const formData = new FormData(form);
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
                        const targetInput = form.querySelector('input[name="target_player_id"]');
                        const defaultPlayerId = form.dataset.defaultPlayerId;
                        const votedForSelf = !targetInput || !defaultPlayerId || targetInput.value === defaultPlayerId;
                        const buttons = form.querySelectorAll('button[type="submit"]');
                        buttons.forEach(btn => {
                            if (votedForSelf) {
                                if (btn.value === status) btn.classList.add('active');
                                else btn.classList.remove('active');
                            }
                            if (data.counts[btn.value] !== undefined) {
                                const countSpan = btn.querySelector('.count');
                                if (countSpan) countSpan.textContent = data.counts[btn.value];
                            }
                        });
                        // Reset target_player_id back to self after voting for someone else
                        if (targetInput && defaultPlayerId) {
                            targetInput.value = defaultPlayerId;
                        }
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

        async function handleVote(event) {
            event.preventDefault();
            const form = event.currentTarget;

            // Zuverlässigere Methode um den Status zu bekommen, falls event.submitter nicht existiert
            let status = event.submitter ? event.submitter.value : null;

            // Falls handleVote anders aufgerufen wurde (nicht über submitter), suchen wir den aktiven Button
            if (!status) {
                // Das ist ein Fallback, falls der Browser event.submitter nicht unterstützt
                return; // Ohne Status können wir nichts tun
            }

            await performVote(form, status);
        }

        function getVoteTargets(form) {
            if (!form || !form.dataset.voteTargets) return [];
            try {
                const parsed = JSON.parse(form.dataset.voteTargets);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function hideVoteTargetMenu() {
            const menu = document.getElementById('voteTargetMenu');
            if (!menu) return;
            menu.classList.remove('open');
            menu.setAttribute('aria-hidden', 'true');
            menu.innerHTML = '';
        }

        function showVoteTargetMenu(button, form, status) {
            const targets = getVoteTargets(form);
            if (targets.length <= 1) return;

            const menu = document.getElementById('voteTargetMenu');
            if (!menu) return;

            menu.innerHTML = '';
            const title = document.createElement('div');
            title.className = 'vote-target-menu-title';
            title.textContent = 'Für wen abstimmen?';
            menu.appendChild(title);

            const list = document.createElement('ul');
            list.className = 'vote-target-menu-list';
            targets.forEach(target => {
                const item = document.createElement('li');
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'vote-target-menu-item';
                btn.textContent = target.name;
                btn.addEventListener('click', () => {
                    const targetInput = form.querySelector('input[name="target_player_id"]');
                    if (targetInput) targetInput.value = target.id;
                    hideVoteTargetMenu();
                    performVote(form, status);
                });
                item.appendChild(btn);
                list.appendChild(item);
            });
            menu.appendChild(list);

            menu.style.left = '50%';
            menu.style.top = '50%';
            menu.classList.add('open');
            menu.setAttribute('aria-hidden', 'false');
        }

        function toggleLocation(checkboxId, containerId) {
            const checkbox = document.getElementById(checkboxId);
            const container = document.getElementById(containerId);
            if (checkbox && container) {
                container.style.display = checkbox.checked ? 'none' : 'grid';
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

            const voteForms = document.querySelectorAll('.vote-form');
            voteForms.forEach(form => {
                const buttons = form.querySelectorAll('button[type="submit"]');
                buttons.forEach(button => {
                    button.addEventListener('contextmenu', function(event) {
                        event.preventDefault();
                        button.dataset.suppressClick = '1';
                        showVoteTargetMenu(button, form, button.value);
                    });

                    let pressTimer = null;
                    button.addEventListener('touchstart', function() {
                        pressTimer = setTimeout(() => {
                            button.dataset.suppressClick = '1';
                            showVoteTargetMenu(button, form, button.value);
                        }, 500);
                    }, { passive: true });

                    button.addEventListener('touchend', function() {
                        if (pressTimer) clearTimeout(pressTimer);
                    });

                    button.addEventListener('touchmove', function() {
                        if (pressTimer) clearTimeout(pressTimer);
                    });

                    button.addEventListener('click', function(event) {
                        if (button.dataset.suppressClick === '1') {
                            event.preventDefault();
                            event.stopPropagation();
                            button.dataset.suppressClick = '';
                        }
                    });
                });
            });

            document.addEventListener('click', function(event) {
                const menu = document.getElementById('voteTargetMenu');
                if (!menu || !menu.classList.contains('open')) return;
                if (!menu.contains(event.target)) {
                    hideVoteTargetMenu();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    hideVoteTargetMenu();
                }
            });

            window.addEventListener('scroll', hideVoteTargetMenu, true);
            window.addEventListener('resize', hideVoteTargetMenu);
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
            openModal('editMatchModal');
        }

        function editTraining(training) {
            document.getElementById('edit_training_id').value = training.id;
            document.getElementById('edit_training_date').value = training.training_date;
            document.getElementById('edit_training_time').value = training.training_time;
            const teamSelect = document.getElementById('edit_training_team_ids');
            Array.from(teamSelect.options).forEach(option => {
                option.selected = training.teams && training.teams.includes(parseInt(option.value));
            });
            openModal('editTrainingModal');
        }

        function editTeam(team) {
            document.getElementById('edit_team_id').value = team.id;
            document.getElementById('edit_team_name').value = team.name;
            openModal('editTeamModal');
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
            const voterPermSelect = document.getElementById('edit_player_voter_permissions');
            if (voterPermSelect) {
                Array.from(voterPermSelect.options).forEach(option => {
                    option.selected = player.voter_permission_player_ids && player.voter_permission_player_ids.includes(parseInt(option.value));
                });
            }
            openModal('editPlayerModal');
        }

        function clearAdminTeamSelection() {
            const adminTeamSelect = document.getElementById('edit_player_admin_team_ids');
            if (!adminTeamSelect) return;
            Array.from(adminTeamSelect.options).forEach(option => {
                option.selected = false;
            });
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
                closeModal(event.target.id);
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
