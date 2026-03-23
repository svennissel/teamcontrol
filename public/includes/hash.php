<?php

/**
 * Generates a random hash of 16 bytes as a base64 string. This is used for player and team authentication.
 * If the length is changed, it will be only changed for new users, not for existing ones.
 *
 * @return String
 * @throws \Random\RandomException
 */
function createHash() : String {
    return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
}
