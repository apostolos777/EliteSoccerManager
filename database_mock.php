<?php
// Simplified mock database system - no real database needed

// Mock Database class that simulates database operations
if (!class_exists('Database')) {
    class Database {
        private $connection;

        public function __construct() {
            // Always use mock - never attempt real database connection
            $this->connection = new MockPDO();
        }

        public function getConnection() {
            return $this->connection;
        }
        
        // Delegate methods to the mock connection
        public function fetchAll($statement, $params = []) {
            return $this->connection->fetchAll($statement, $params);
        }
        
        public function fetchOne($statement, $params = []) {
            return $this->connection->fetchOne($statement, $params);
        }
        
        public function insert($table, $data) {
            // Mock insert - just return a random ID
            return rand(1, 1000);
        }
        
        public function update($table, $data, $where, $params = []) {
            // Mock update - just return true
            return true;
        }
        
        public function delete($table, $where, $params = []) {
            // Mock delete - just return true
            return true;
        }
        
        public function prepare($statement) {
            return $this->connection->prepare($statement);
        }
        
        public function query($statement) {
            return $this->connection->query($statement);
        }
    }
}

// Mock PDO class that simulates database operations
if (!class_exists('MockPDO')) {
    class MockPDO {
        
        public function prepare($statement) {
            return new MockPDOStatement();
        }
        
        public function query($statement) {
            return new MockPDOStatement();
        }
        
        // Add direct fetchAll method for compatibility
        public function fetchAll($statement, $params = []) {
            // Return mock data based on the query
            if (strpos($statement, 'teams') !== false) {
                return [
                    // Original teams
                    [
                        'id' => 1, 
                        'name' => 'VIVO United FC', 
                        'organization_name' => 'VIVO UNITED', 
                        'coach_name' => 'John Smith', 
                        'founded_year' => 2020,
                        'age_group' => 'U18',
                        'home_ground' => 'VIVO Stadium',
                        'formation' => '4-4-2',
                        'logo_url' => '',
                        'description' => 'Premier youth football club focused on developing young talent and promoting excellence both on and off the field.'
                    ],
                    [
                        'id' => 2, 
                        'name' => 'Elite Strikers', 
                        'organization_name' => 'VIVO UNITED', 
                        'coach_name' => 'Sarah Johnson', 
                        'founded_year' => 2019,
                        'age_group' => 'U16',
                        'home_ground' => 'Training Ground',
                        'formation' => '3-5-2',
                        'logo_url' => '',
                        'description' => 'Competitive team specializing in attacking play and player development through innovative training methods.'
                    ],
                    [
                        'id' => 3, 
                        'name' => 'Rising Stars', 
                        'organization_name' => 'VIVO UNITED', 
                        'coach_name' => 'Mike Wilson', 
                        'founded_year' => 2021,
                        'age_group' => 'U14',
                        'home_ground' => 'Community Field',
                        'formation' => '4-3-3',
                        'logo_url' => '',
                        'description' => 'Nurturing the next generation of football stars with emphasis on fundamental skills and team spirit.'
                    ],
                    // New VIVO United teams from import
                    ['id' => 4, 'name' => 'VIVO United U6', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U6', 'coach_name' => 'Emma Foster', 'description' => 'Under 6 youth team'],
                    ['id' => 5, 'name' => 'VIVO United U8', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U8', 'coach_name' => 'James Mitchell', 'description' => 'Under 8 youth team'],
                    ['id' => 6, 'name' => 'VIVO United U10', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U10', 'coach_name' => 'Lisa Chen', 'description' => 'Under 10 youth team'],
                    ['id' => 7, 'name' => 'VIVO United U12', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U12', 'coach_name' => 'Michael Brown', 'description' => 'Under 12 youth team'],
                    ['id' => 8, 'name' => 'VIVO United U14', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U14', 'coach_name' => 'Sarah Davis', 'description' => 'Under 14 youth team'],
                    ['id' => 9, 'name' => 'VIVO United U16', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U16', 'coach_name' => 'David Wilson', 'description' => 'Under 16 youth team'],
                    ['id' => 10, 'name' => 'VIVO United U18', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U18', 'coach_name' => 'Rachel Green', 'description' => 'Under 18 youth team'],
                    ['id' => 11, 'name' => 'VIVO United U21', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U21', 'coach_name' => 'Kevin Martinez', 'description' => 'Under 21 development team'],
                    ['id' => 12, 'name' => 'VIVO United U23', 'organization_name' => 'VIVO UNITED', 'age_group' => 'U23', 'coach_name' => 'Angela Rodriguez', 'description' => 'Under 23 development team'],
                    ['id' => 13, 'name' => 'VIVO United First Team', 'organization_name' => 'VIVO UNITED', 'age_group' => 'Senior', 'coach_name' => 'Roberto Silva', 'description' => 'Senior first team'],
                    ['id' => 14, 'name' => 'VIVO United Staff & Volunteers', 'organization_name' => 'VIVO UNITED', 'age_group' => 'Staff', 'coach_name' => 'Administration', 'description' => 'Club staff and volunteers']
                ];
            } elseif (strpos($statement, 'players') !== false) {
                return [
                    // Original players
                    [
                        'id' => 1, 
                        'first_name' => 'Marcus', 
                        'last_name' => 'Rodriguez', 
                        'position' => 'Forward', 
                        'jersey_number' => 10, 
                        'team_name' => 'VIVO United FC',
                        'team_id' => 1,
                        'date_of_birth' => '2005-03-15',
                        'gender' => 'M',
                        'nationality' => 'South Africa',
                        'profile_picture_url' => '',
                        'unique_player_id' => '250715-MR-00001',
                        'emergency_contact' => '+27 123 456 789'
                    ],
                    [
                        'id' => 2, 
                        'first_name' => 'Alex', 
                        'last_name' => 'Thompson', 
                        'position' => 'Midfielder', 
                        'jersey_number' => 8, 
                        'team_name' => 'VIVO United FC',
                        'team_id' => 1,
                        'date_of_birth' => '2006-01-22',
                        'gender' => 'M',
                        'nationality' => 'South Africa',
                        'profile_picture_url' => '',
                        'unique_player_id' => '250715-AT-00002',
                        'emergency_contact' => '+27 987 654 321'
                    ],
                    [
                        'id' => 3, 
                        'first_name' => 'David', 
                        'last_name' => 'Chen', 
                        'position' => 'Defender', 
                        'jersey_number' => 5, 
                        'team_name' => 'Elite Strikers',
                        'team_id' => 2,
                        'date_of_birth' => '2007-09-10',
                        'gender' => 'M',
                        'nationality' => 'South Africa',
                        'profile_picture_url' => '',
                        'unique_player_id' => '250715-DC-00003',
                        'emergency_contact' => '+27 555 123 456'
                    ],
                    [
                        'id' => 4, 
                        'first_name' => 'Sofia', 
                        'last_name' => 'Martinez', 
                        'position' => 'Goalkeeper', 
                        'jersey_number' => 1, 
                        'team_name' => 'Rising Stars',
                        'team_id' => 3,
                        'date_of_birth' => '2008-12-05',
                        'gender' => 'F',
                        'nationality' => 'South Africa',
                        'profile_picture_url' => '',
                        'unique_player_id' => '250715-SM-00004',
                        'emergency_contact' => '+27 444 567 890'
                    ],
                    // VIVO United U6 players
                    ['id' => 5, 'first_name' => 'Liam Jeffrey', 'last_name' => 'Brummer', 'date_of_birth' => '2018-05-12', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Forward', 'unique_player_id' => '180512-LJB-31496', 'email' => 'liam.jeffrey.brummer@vivounited.com'],
                    ['id' => 6, 'first_name' => 'Raife Stephen Dahl', 'last_name' => 'Prain', 'date_of_birth' => '2018-03-11', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Midfielder', 'unique_player_id' => '180311-RSDP-35530', 'email' => 'raife.stephen.dahl.prain@vivounited.com'],
                    ['id' => 7, 'first_name' => 'Jamie Kerr', 'last_name' => 'Smith', 'date_of_birth' => '2018-09-07', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Defender', 'unique_player_id' => '180907-JKS-56460', 'email' => 'jamie.kerr.smith@vivounited.com'],
                    ['id' => 8, 'first_name' => 'Ruben', 'last_name' => 'Vogt', 'date_of_birth' => '2018-10-10', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Forward', 'unique_player_id' => '181010-RV-52555', 'email' => 'ruben.vogt@vivounited.com'],
                    ['id' => 9, 'first_name' => 'Lucca', 'last_name' => 'Wood', 'date_of_birth' => '2018-06-18', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Midfielder', 'unique_player_id' => '180618-LW-17025', 'email' => 'lucca.wood@vivounited.com'],
                    ['id' => 10, 'first_name' => 'Gabriele', 'last_name' => 'Gioia', 'date_of_birth' => '2019-10-02', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Forward', 'unique_player_id' => '191002-GG-40637', 'email' => 'gabriele.gioia@vivounited.com'],
                    ['id' => 11, 'first_name' => 'Will', 'last_name' => 'Dignum', 'date_of_birth' => '2019-06-20', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Defender', 'unique_player_id' => '190620-WD-51937', 'email' => 'will.dignum@vivounited.com'],
                    ['id' => 12, 'first_name' => 'Reece', 'last_name' => 'Labuschagne', 'date_of_birth' => '2019-02-25', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Midfielder', 'unique_player_id' => '190225-RL-07596', 'email' => 'reece.labuschagne@vivounited.com'],
                    ['id' => 13, 'first_name' => 'Ronan Alexander', 'last_name' => 'Glöss', 'date_of_birth' => '2019-02-09', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Forward', 'unique_player_id' => '190209-RAG-39568', 'email' => 'ronan.alexander.glöss@vivounited.com'],
                    ['id' => 14, 'first_name' => 'Matteo Joshua', 'last_name' => 'Ferreira', 'date_of_birth' => '2019-11-03', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Defender', 'unique_player_id' => '191103-MJF-54473', 'email' => 'matteo.joshua.ferreira@vivounited.com'],
                    ['id' => 15, 'first_name' => 'Fynn', 'last_name' => 'Ovenstone', 'date_of_birth' => '2020-04-09', 'team_name' => 'VIVO United U6', 'team_id' => 4, 'position' => 'Midfielder', 'unique_player_id' => '200409-FO-17062', 'email' => 'fynn.ovenstone@vivounited.com'],
                    // Other age groups
                    ['id' => 16, 'first_name' => 'Marcus', 'last_name' => 'Thompson', 'date_of_birth' => '2016-03-15', 'team_name' => 'VIVO United U8', 'team_id' => 5, 'position' => 'Forward', 'jersey_number' => 9, 'unique_player_id' => '160315-MT-12345', 'email' => 'marcus.thompson@vivounited.com'],
                    ['id' => 17, 'first_name' => 'Sophie', 'last_name' => 'Williams', 'date_of_birth' => '2014-07-22', 'team_name' => 'VIVO United U10', 'team_id' => 6, 'position' => 'Midfielder', 'jersey_number' => 8, 'unique_player_id' => '140722-SW-67890', 'email' => 'sophie.williams@vivounited.com'],
                    ['id' => 18, 'first_name' => 'Diego', 'last_name' => 'Rodriguez', 'date_of_birth' => '2012-11-08', 'team_name' => 'VIVO United U12', 'team_id' => 7, 'position' => 'Defender', 'jersey_number' => 4, 'unique_player_id' => '121108-DR-45678', 'email' => 'diego.rodriguez@vivounited.com'],
                    ['id' => 19, 'first_name' => 'Emma', 'last_name' => 'Johnson', 'date_of_birth' => '2010-05-30', 'team_name' => 'VIVO United U14', 'team_id' => 8, 'position' => 'Goalkeeper', 'jersey_number' => 1, 'unique_player_id' => '100530-EJ-23456', 'email' => 'emma.johnson@vivounited.com'],
                    ['id' => 20, 'first_name' => 'Alessandro', 'last_name' => 'Rossi', 'date_of_birth' => '2008-09-14', 'team_name' => 'VIVO United U16', 'team_id' => 9, 'position' => 'Forward', 'jersey_number' => 10, 'unique_player_id' => '080914-AR-78901', 'email' => 'alessandro.rossi@vivounited.com'],
                    ['id' => 21, 'first_name' => 'Mia', 'last_name' => 'Anderson', 'date_of_birth' => '2006-12-03', 'team_name' => 'VIVO United U18', 'team_id' => 10, 'position' => 'Midfielder', 'jersey_number' => 6, 'unique_player_id' => '061203-MA-34567', 'email' => 'mia.anderson@vivounited.com'],
                    ['id' => 22, 'first_name' => 'Lucas', 'last_name' => 'Silva', 'date_of_birth' => '2004-04-18', 'team_name' => 'VIVO United U21', 'team_id' => 11, 'position' => 'Defender', 'jersey_number' => 3, 'unique_player_id' => '040418-LS-89012', 'email' => 'lucas.silva@vivounited.com'],
                    ['id' => 23, 'first_name' => 'Isabella', 'last_name' => 'Martinez', 'date_of_birth' => '2002-08-25', 'team_name' => 'VIVO United U23', 'team_id' => 12, 'position' => 'Forward', 'jersey_number' => 11, 'unique_player_id' => '020825-IM-56789', 'email' => 'isabella.martinez@vivounited.com'],
                    ['id' => 24, 'first_name' => 'Carlos', 'last_name' => 'Santos', 'date_of_birth' => '1995-01-12', 'team_name' => 'VIVO United First Team', 'team_id' => 13, 'position' => 'Midfielder', 'jersey_number' => 8, 'unique_player_id' => '950112-CS-12309', 'email' => 'carlos.santos@vivounited.com'],
                    ['id' => 25, 'first_name' => 'Maria', 'last_name' => 'Garcia', 'date_of_birth' => '1992-06-07', 'team_name' => 'VIVO United First Team', 'team_id' => 13, 'position' => 'Captain/Midfielder', 'jersey_number' => 5, 'unique_player_id' => '920607-MG-45612', 'email' => 'maria.garcia@vivounited.com']
                ];
            } elseif (strpos($statement, 'events') !== false) {
                return [
                    [
                        'id' => 1, 
                        'title' => 'Championship Final', 
                        'name' => 'Championship Final',
                        'date' => '2025-08-15', 
                        'event_date' => '2025-08-15',
                        'start_time' => '15:00',
                        'location' => 'VIVO Stadium', 
                        'type' => 'Match',
                        'event_type' => 'Match',
                        'team_id' => 1,
                        'team_name' => 'VIVO United FC',
                        'description' => 'Final match of the championship'
                    ],
                    [
                        'id' => 2, 
                        'title' => 'Team Training Session', 
                        'name' => 'Team Training Session',
                        'date' => '2025-07-25', 
                        'event_date' => '2025-07-25',
                        'start_time' => '17:00',
                        'location' => 'Training Ground', 
                        'type' => 'Training',
                        'event_type' => 'Training',
                        'team_id' => 1,
                        'team_name' => 'VIVO United FC',
                        'description' => 'Regular training session'
                    ],
                    [
                        'id' => 3, 
                        'title' => 'Youth League Match', 
                        'name' => 'Youth League Match',
                        'date' => '2025-08-01', 
                        'event_date' => '2025-08-01',
                        'start_time' => '14:00',
                        'location' => 'Community Field', 
                        'type' => 'Match',
                        'event_type' => 'Match',
                        'team_id' => 2,
                        'team_name' => 'Elite Strikers',
                        'description' => 'League match against rivals'
                    ]
                ];
            }
            
            return []; // Default empty array
        }
        
        // Add direct fetchOne method for compatibility
        public function fetchOne($statement, $params = []) {
            // Return mock data based on the query
            if (strpos($statement, 'COUNT(*)') !== false) {
                return ['count' => rand(5, 25)]; // Random count for teams/players
            } elseif (strpos($statement, 'teams') !== false && strpos($statement, 'WHERE') !== false) {
                return [
                    'id' => 1, 
                    'name' => 'VIVO United FC', 
                    'organization_name' => 'VIVO UNITED', 
                    'coach_name' => 'John Smith', 
                    'founded_year' => 2020,
                    'age_group' => 'U18',
                    'home_ground' => 'VIVO Stadium',
                    'formation' => '4-4-2',
                    'logo_url' => '',
                    'description' => 'Premier youth football club focused on developing young talent and promoting excellence both on and off the field.'
                ];
            } elseif (strpos($statement, 'players') !== false && strpos($statement, 'WHERE') !== false) {
                return [
                    'id' => 1, 
                    'first_name' => 'Marcus', 
                    'last_name' => 'Rodriguez', 
                    'position' => 'Forward', 
                    'jersey_number' => 10, 
                    'team_name' => 'VIVO United FC',
                    'team_id' => 1,
                    'date_of_birth' => '2005-03-15',
                    'gender' => 'M',
                    'nationality' => 'South Africa',
                    'profile_picture_url' => '',
                    'unique_player_id' => '250715-MR-00001',
                    'emergency_contact' => '+27 123 456 789',
                    'age_group' => 'U18'
                ];
            } elseif (strpos($statement, 'events') !== false && strpos($statement, 'WHERE') !== false) {
                return [
                    'id' => 1, 
                    'title' => 'Championship Final',
                    'name' => 'Championship Final', 
                    'date' => '2025-08-15',
                    'event_date' => '2025-08-15',
                    'start_time' => '15:00',
                    'location' => 'VIVO Stadium', 
                    'type' => 'Match',
                    'event_type' => 'Match',
                    'team_id' => 1,
                    'team_name' => 'VIVO United FC'
                ];
            }
            
            return false; // Default no result
        }
        
        public function lastInsertId() {
            return rand(1, 1000);
        }
        
        public function exec($statement) {
            return true;
        }
        
        public function setAttribute($attribute, $value) {
            return true;
        }
        
        public function errorCode() {
            return '00000'; // Success code
        }
        
        public function errorInfo() {
            return ['00000', 0, 'Success'];
        }
    }
}

if (!class_exists('MockPDOStatement')) {
    class MockPDOStatement {
        public function execute($params = []) {
            return true;
        }
        
        public function fetch($fetch_style = null) {
            return false; // No data by default
        }
        
        public function fetchAll($fetch_style = null) {
            return []; // Empty array by default
        }
        
        public function rowCount() {
            return 0;
        }
        
        public function bindParam($param, &$variable, $type = null) {
            return true;
        }
        
        public function bindValue($param, $value, $type = null) {
            return true;
        }
        
        public function errorCode() {
            return '00000';
        }
        
        public function errorInfo() {
            return ['00000', 0, 'Success'];
        }
    }
}
?>
