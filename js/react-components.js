/**
 * VIVO United - React Web Components (CDN-based, no Node.js required)
 * Modern reactive UI components using React + ReactDOM via CDN
 */

// React and ReactDOM are loaded via CDN in the page head
// This file contains all React component definitions

const { useState, useEffect, useCallback } = React;

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Fetch data from API endpoint
 */
async function fetchAPI(endpoint) {
    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('API fetch error:', error);
        throw error;
    }
}

// ============================================================================
// DASHBOARD COMPONENTS
// ============================================================================

/**
 * Dashboard Stats Card Component
 */
function DashboardStatCard({ icon, label, value, trend, loading }) {
    return React.createElement('div', { className: 'fc-card stat-card' },
        loading ? 
            React.createElement('div', { className: 'loading' }, 'Loading...') :
            [
                React.createElement('div', { className: 'stat-icon', key: 'icon' },
                    React.createElement('i', { className: icon })
                ),
                React.createElement('div', { className: 'stat-content', key: 'content' },
                    React.createElement('div', { className: 'num' }, value),
                    React.createElement('div', { className: 'label' }, label),
                    trend && React.createElement('div', { className: 'trend', key: 'trend' }, trend)
                )
            ]
    );
}

/**
 * Dashboard Stats Container
 */
function DashboardStats() {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchAPI('api/dashboard.php')
            .then(data => {
                setStats(data.stats);
                setLoading(false);
            })
            .catch(err => {
                setError(err.message);
                setLoading(false);
            });
    }, []);

    if (error) {
        return React.createElement('div', { className: 'error-message' }, 
            'Failed to load dashboard stats: ' + error
        );
    }

    const statCards = [
        { icon: 'fas fa-users', label: 'Total Players', key: 'players' },
        { icon: 'fas fa-users-cog', label: 'Total Teams', key: 'teams' },
        { icon: 'fas fa-calendar-alt', label: 'Upcoming Events', key: 'events' },
        { icon: 'fas fa-clipboard-check', label: 'Attendance Rate', key: 'attendance', suffix: '%' }
    ];

    return React.createElement('div', { className: 'dashboard-stats' },
        statCards.map(card =>
            React.createElement(DashboardStatCard, {
                key: card.key,
                icon: card.icon,
                label: card.label,
                value: loading ? '...' : (stats ? (stats[card.key] || 0) + (card.suffix || '') : '0'),
                loading: loading
            })
        )
    );
}

/**
 * Upcoming Match Widget
 */
function UpcomingMatches() {
    const [matches, setMatches] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchAPI('api/dashboard.php?section=matches')
            .then(data => {
                setMatches(data.matches || []);
                setLoading(false);
            })
            .catch(() => setLoading(false));
    }, []);

    return React.createElement('div', { className: 'fc-card stack-card' },
        React.createElement('h3', { className: 'card-title' }, 'Upcoming Matches'),
        React.createElement('div', { className: 'match-list' },
            loading ? 
                React.createElement('p', null, 'Loading...') :
                matches.length === 0 ?
                    React.createElement('p', { className: 'empty-state' }, 'No upcoming matches') :
                    matches.map((match, idx) =>
                        React.createElement('div', { className: 'match-item', key: idx },
                            React.createElement('div', { className: 'match-date' }, match.date),
                            React.createElement('div', { className: 'match-teams' }, match.teams),
                            React.createElement('div', { className: 'match-venue' }, match.venue)
                        )
                    )
        )
    );
}

/**
 * Top Scorers Widget
 */
function TopScorers() {
    const [scorers, setScorers] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchAPI('api/dashboard.php?section=scorers')
            .then(data => {
                setScorers(data.scorers || []);
                setLoading(false);
            })
            .catch(() => setLoading(false));
    }, []);

    return React.createElement('div', { className: 'fc-card stack-card' },
        React.createElement('h3', { className: 'card-title' }, 'Top Scorers'),
        React.createElement('div', { className: 'scorers-list' },
            loading ?
                React.createElement('p', null, 'Loading...') :
                scorers.length === 0 ?
                    React.createElement('p', { className: 'empty-state' }, 'No scoring data yet') :
                    scorers.map((scorer, idx) =>
                        React.createElement('div', { className: 'scorer-item', key: idx },
                            React.createElement('div', { className: 'scorer-rank' }, idx + 1),
                            React.createElement('div', { className: 'scorer-name' }, scorer.name),
                            React.createElement('div', { className: 'scorer-goals' }, scorer.goals)
                        )
                    )
        )
    );
}

// ============================================================================
// PLAYERS COMPONENTS
// ============================================================================

/**
 * Player Card Component
 */
function PlayerCard({ player, onEdit, onDelete }) {
    const photoUrl = player.photo_url || 'https://via.placeholder.com/150x150/081224/ffffff?text=' + (player.first_name?.[0] || 'P') + (player.last_name?.[0] || '');
    
    return React.createElement('div', { className: 'fc-card player-card' },
        React.createElement('div', { className: 'player-photo' },
            React.createElement('img', { 
                src: photoUrl, 
                alt: player.first_name + ' ' + player.last_name,
                onError: (e) => { e.target.src = 'https://via.placeholder.com/150x150/081224/ffffff?text=' + (player.first_name?.[0] || 'P') + (player.last_name?.[0] || ''); }
            })
        ),
        React.createElement('div', { className: 'player-header' },
            React.createElement('h3', null, player.first_name + ' ' + player.last_name),
            player.jersey_number && 
                React.createElement('span', { className: 'jersey-badge' }, '#' + player.jersey_number)
        ),
        React.createElement('div', { className: 'player-info' },
            React.createElement('div', { className: 'info-row' },
                React.createElement('span', { className: 'label' }, 'Team:'),
                React.createElement('span', { className: 'value' }, player.team_name || 'Not assigned')
            ),
            React.createElement('div', { className: 'info-row' },
                React.createElement('span', { className: 'label' }, 'DOB:'),
                React.createElement('span', { className: 'value' }, player.date_of_birth || 'N/A')
            ),
            player.email && React.createElement('div', { className: 'info-row' },
                React.createElement('span', { className: 'label' }, 'Email:'),
                React.createElement('span', { className: 'value' }, player.email)
            )
        ),
        React.createElement('div', { className: 'player-actions' },
            React.createElement('button', { 
                className: 'btn-secondary', 
                onClick: () => onEdit(player.id) 
            }, 'Edit'),
            React.createElement('button', { 
                className: 'btn-danger', 
                onClick: () => onDelete(player.id) 
            }, 'Delete')
        )
    );
}

/**
 * Players List with Search and Filter
 */
function PlayersList() {
    const [players, setPlayers] = useState([]);
    const [filteredPlayers, setFilteredPlayers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [teamFilter, setTeamFilter] = useState('all');
    const [teams, setTeams] = useState([]);

    useEffect(() => {
        Promise.all([
            fetchAPI('api/players.php'),
            fetchAPI('api/teams.php')
        ])
        .then(([playersData, teamsData]) => {
            setPlayers(playersData.players || []);
            setFilteredPlayers(playersData.players || []);
            setTeams(teamsData.teams || []);
            setLoading(false);
        })
        .catch(() => setLoading(false));
    }, []);

    // Filter players when search or team filter changes
    useEffect(() => {
        let filtered = players;

        // Apply search filter
        if (searchTerm) {
            const term = searchTerm.toLowerCase();
            filtered = filtered.filter(p => 
                (p.first_name + ' ' + p.last_name).toLowerCase().includes(term) ||
                (p.jersey_number && p.jersey_number.toString().includes(term))
            );
        }

        // Apply team filter
        if (teamFilter !== 'all') {
            filtered = filtered.filter(p => p.team_id == teamFilter);
        }

        setFilteredPlayers(filtered);
    }, [searchTerm, teamFilter, players]);

    const handleEdit = (playerId) => {
        window.location.href = 'edit_player.php?id=' + playerId;
    };

    const handleDelete = (playerId) => {
        if (confirm('Are you sure you want to delete this player?')) {
            fetch('delete_player.php?id=' + playerId, { method: 'POST' })
                .then(() => {
                    setPlayers(players.filter(p => p.id !== playerId));
                })
                .catch(err => alert('Failed to delete player: ' + err.message));
        }
    };

    if (loading) {
        return React.createElement('div', { className: 'loading-container' }, 'Loading players...');
    }

    return React.createElement('div', { className: 'players-container' },
        // Search and Filter Bar
        React.createElement('div', { className: 'fc-card filters-bar' },
            React.createElement('input', {
                type: 'text',
                placeholder: 'Search players...',
                className: 'search-input',
                value: searchTerm,
                onChange: (e) => setSearchTerm(e.target.value)
            }),
            React.createElement('select', {
                className: 'team-filter',
                value: teamFilter,
                onChange: (e) => setTeamFilter(e.target.value)
            },
                React.createElement('option', { value: 'all' }, 'All Teams'),
                teams.map(team =>
                    React.createElement('option', { value: team.id, key: team.id }, team.name)
                )
            ),
            React.createElement('span', { className: 'results-count' }, 
                filteredPlayers.length + ' player' + (filteredPlayers.length !== 1 ? 's' : '')
            )
        ),
        // Players Grid
        React.createElement('div', { className: 'players-grid' },
            filteredPlayers.length === 0 ?
                React.createElement('div', { className: 'empty-state' }, 'No players found') :
                filteredPlayers.map(player =>
                    React.createElement(PlayerCard, {
                        key: player.id,
                        player: player,
                        onEdit: handleEdit,
                        onDelete: handleDelete
                    })
                )
        )
    );
}

// ============================================================================
// TEAMS COMPONENTS
// ============================================================================

/**
 * Team Card Component
 */
function TeamCard({ team, onEdit, onDelete, onViewDetails }) {
    return React.createElement('div', { className: 'fc-card team-card' },
        React.createElement('div', { className: 'team-header' },
            React.createElement('h3', null, team.name),
            team.age_group && 
                React.createElement('span', { className: 'age-badge' }, team.age_group)
        ),
        React.createElement('div', { className: 'team-stats' },
            React.createElement('div', { className: 'stat' },
                React.createElement('div', { className: 'num' }, team.player_count || 0),
                React.createElement('div', { className: 'label' }, 'Players')
            ),
            React.createElement('div', { className: 'stat' },
                React.createElement('div', { className: 'num' }, team.coach_name || 'N/A'),
                React.createElement('div', { className: 'label' }, 'Coach')
            )
        ),
        React.createElement('div', { className: 'team-actions' },
            React.createElement('button', { 
                className: 'btn-primary', 
                onClick: () => onViewDetails(team.id) 
            }, 'View Details'),
            React.createElement('button', { 
                className: 'btn-secondary', 
                onClick: () => onEdit(team.id) 
            }, 'Edit'),
            React.createElement('button', { 
                className: 'btn-danger', 
                onClick: () => onDelete(team.id) 
            }, 'Delete')
        )
    );
}

/**
 * Teams List Component
 */
function TeamsList() {
    const [teams, setTeams] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchAPI('api/teams.php')
            .then(data => {
                setTeams(data.teams || []);
                setLoading(false);
            })
            .catch(() => setLoading(false));
    }, []);

    const handleEdit = (teamId) => {
        window.location.href = 'edit_team.php?id=' + teamId;
    };

    const handleDelete = (teamId) => {
        if (confirm('Are you sure you want to delete this team?')) {
            fetch('delete_team.php?id=' + teamId, { method: 'POST' })
                .then(() => {
                    setTeams(teams.filter(t => t.id !== teamId));
                })
                .catch(err => alert('Failed to delete team: ' + err.message));
        }
    };

    const handleViewDetails = (teamId) => {
        window.location.href = 'team_details.php?id=' + teamId;
    };

    if (loading) {
        return React.createElement('div', { className: 'loading-container' }, 'Loading teams...');
    }

    return React.createElement('div', { className: 'teams-container' },
        React.createElement('div', { className: 'teams-grid' },
            teams.length === 0 ?
                React.createElement('div', { className: 'empty-state' }, 'No teams found') :
                teams.map(team =>
                    React.createElement(TeamCard, {
                        key: team.id,
                        team: team,
                        onEdit: handleEdit,
                        onDelete: handleDelete,
                        onViewDetails: handleViewDetails
                    })
                )
        )
    );
}

// ============================================================================
// COMPONENT INITIALIZERS
// ============================================================================

/**
 * Initialize React components on page load
 */
window.initReactComponents = function() {
    // Dashboard components
    const dashboardStatsRoot = document.getElementById('react-dashboard-stats');
    if (dashboardStatsRoot) {
        ReactDOM.render(React.createElement(DashboardStats), dashboardStatsRoot);
    }

    const upcomingMatchesRoot = document.getElementById('react-upcoming-matches');
    if (upcomingMatchesRoot) {
        ReactDOM.render(React.createElement(UpcomingMatches), upcomingMatchesRoot);
    }

    const topScorersRoot = document.getElementById('react-top-scorers');
    if (topScorersRoot) {
        ReactDOM.render(React.createElement(TopScorers), topScorersRoot);
    }

    // Players page
    const playersListRoot = document.getElementById('react-players-list');
    if (playersListRoot) {
        ReactDOM.render(React.createElement(PlayersList), playersListRoot);
    }

    // Teams page
    const teamsListRoot = document.getElementById('react-teams-list');
    if (teamsListRoot) {
        ReactDOM.render(React.createElement(TeamsList), teamsListRoot);
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initReactComponents);
} else {
    window.initReactComponents();
}
