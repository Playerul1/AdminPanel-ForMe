<?php
/**
 * SSA Admin Panel - Config principal
 * IMPORTANT: completează valorile "CHANGE_ME"
 */

return [
    // App
    'app' => [
        'name' => 'SmartSoftArt Admin',
        'env' => 'production', // production | local
        'debug' => false,      // true doar la teste
        'timezone' => 'Europe/Chisinau',

        // Setează URL-ul exact al panelului (fără slash la final)
        'base_url' => 'https://admin.smartsoftart.com',

        // Cheie random pentru sesiuni/token-uri (schimbă!)
        // Poți pune orice string lung (minim 32 caractere)
        'app_key' => '2f8d1c9a7b4e6d3f0a1c5e9b7d2f4a6c8e1b3d5f7a9c2e4b6d8f0a1c3e5b7d9f',
    ],

    /**
     * Baze de date
     * - panel_db: baza de date a panelului (lead-uri, task-uri, setări etc.)
     * - whmcs_db: baza de date a WHMCS (citire pentru clienți/facturi/comenzi/admini)
     *
     * Dacă vrei, poți folosi același MySQL user/parolă, dar recomand DB separat.
     */
    'db' => [
        'panel_db' => [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'smart_admin',
            'username' => 'smart_admin',
            'password' => '4HC6RztTsTeUMVBUynQA',
            'charset' => 'utf8mb4',
        ],

        'whmcs_db' => [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'smart_whmcs',
            'username' => 'smart_whmcs',
            'password' => 'Q5QYkKKt2QpbVryNJBGJ',
            'charset' => 'utf8mb4',
        ],
    ],

    /**
     * WHMCS Bridge (o să-l facem în pașii următori)
     * Ideea: panelul NU trebuie să acceseze direct WHMCS DB dacă nu vrei.
     * Poate folosi bridge API securizat.
     */
    'whmcs_bridge' => [
        // o să fie endpoint-ul din WHMCS (pe domeniul principal)
        'url' => 'https://smartsoftart.com/modules/addons/ssa_bridge/api.php',

        // cheie secretă comună (o setăm identic și în modulul bridge)
        'secret' => 'CHANGE_ME__BRIDGE_SHARED_SECRET',
        'timeout_seconds' => 12,
    ],

    /**
     * Sesiuni
     * Pentru shared hosting, lăsăm file-based sessions.
     */
    'session' => [
        'name' => 'ssa_admin_session',
        'cookie_secure' => true,     // true dacă ai SSL (ai)
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],

    /**
     * Auth (temporar)
     * - master_key: dacă e gol, se folosește app.app_key
     * - master_uid: trebuie să fie > 0 (altfel rutele de "Accept" nu rulează)
     */
    'auth' => [
        'master_key' => '',
        'master_uid' => 1,
        'master_name' => 'Owner',
        'master_role' => 'Owner',
    ],
];
