/**
 * Load the page again from the server, which injects the scripts of the accepted
 * categories: scripts already running cannot be taken back any other way.
 */
export function reloadPage(): void {
    window.location.reload();
}
