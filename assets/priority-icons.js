/**
 * Priority Icons - JavaScript
 *
 * Replaces priority text labels with visual icons in osTicket Staff Panel.
 * Handles initial page load and PJAX navigation.
 *
 * @package    osTicket\Plugins\PriorityIcons
 * @author     Markus Michalski
 * @version    1.0.0
 */

(function (window, document) {
    'use strict';

    // =========================================================================
    // Configuration
    // =========================================================================

    /**
     * Plugin configuration from PHP (injected inline).
     * Falls back to defaults if not available.
     *
     * @type {Object}
     */
    const CONFIG = window.PriorityIconsConfig || {
        priorities: {
            // English names
            Emergency: {
                icon: 'exclamation-circle',
                color: '#dc3545',
                class: 'priority-emergency',
            },
            High: {
                icon: 'arrow-up',
                color: '#fd7e14',
                class: 'priority-high',
            },
            Normal: {
                icon: 'minus',
                color: '#ffc107',
                class: 'priority-normal',
            },
            Low: {
                icon: 'arrow-down',
                color: '#28a745',
                class: 'priority-low',
            },
            // German names (Aliases)
            Notfall: {
                icon: 'exclamation-circle',
                color: '#dc3545',
                class: 'priority-emergency',
            },
            Hoch: {
                icon: 'arrow-up',
                color: '#fd7e14',
                class: 'priority-high',
            },
            Niedrig: {
                icon: 'arrow-down',
                color: '#28a745',
                class: 'priority-low',
            },
        },
        debug: false,
    };

    /**
     * Priority names for text matching.
     *
     * @type {string[]}
     */
    const PRIORITY_NAMES = Object.keys(CONFIG.priorities);

    /**
     * Regex pattern for exact priority name match.
     * Matches only if entire trimmed text equals a priority name.
     *
     * @type {RegExp}
     */
    const PRIORITY_REGEX = new RegExp(`^(${PRIORITY_NAMES.join('|')})$`);

    /**
     * Containers to scan for priority text (most specific first).
     *
     * @type {string[]}
     */
    const TARGET_CONTAINERS = [
        'table.list.queue.tickets',
        '.ticket_info',
        '.ticket-info',
        '#ticket-status',
        '.pjax-container',
    ];

    // =========================================================================
    // Utility Functions
    // =========================================================================

    /**
     * Log debug messages if debug mode is enabled.
     *
     * @param {...*} args - Arguments to log
     */
    const log = (...args) => {
        if (CONFIG.debug) {
            console.log('[PriorityIcons]', ...args);
        }
    };

    /**
     * Check if element or any ancestor is already processed.
     * Prevents infinite recursion from MutationObserver re-processing
     * sr-only text inside priority-wrapper elements.
     *
     * @param {Element} element - Element to check
     * @returns {boolean} True if already processed
     */
    const isProcessed = (element) => {
        return !!element.closest('[data-priority-icon], .priority-wrapper, .sr-only');
    };

    /**
     * Mark element as processed with priority name.
     *
     * @param {Element} element - Element to mark
     * @param {string} priority - Priority name
     */
    const markProcessed = (element, priority) => {
        element.setAttribute('data-priority-icon', priority);
    };

    // =========================================================================
    // DOM Manipulation
    // =========================================================================

    /**
     * Create icon element for a priority.
     *
     * @param {string} priority - Priority name (e.g., 'Emergency')
     * @returns {HTMLSpanElement|null} Icon element or null if invalid priority
     */
    const createIcon = (priority) => {
        const config = CONFIG.priorities[priority];
        if (!config) {
            return null;
        }

        const icon = document.createElement('span');
        icon.className = `priority-icon ${config.class}`;
        icon.setAttribute('title', priority);
        icon.setAttribute('aria-label', `Priority: ${priority}`);
        icon.style.setProperty('--priority-color', config.color);

        return icon;
    };

    /**
     * Process a single text node and replace with icon if it matches.
     *
     * @param {Text} node - Text node to process
     * @returns {boolean} True if replacement was made
     */
    const processTextNode = (node) => {
        const text = node.textContent.trim();

        // Check if text is an exact priority match
        if (!PRIORITY_REGEX.test(text)) {
            return false;
        }

        const priority = text;
        const parent = node.parentElement;

        // Skip if already processed or no parent
        if (!parent || isProcessed(parent)) {
            return false;
        }

        // Skip if parent is a script, style, or similar element
        const tagName = parent.tagName.toLowerCase();
        if (['script', 'style', 'textarea', 'input'].includes(tagName)) {
            return false;
        }

        // Create icon element
        const icon = createIcon(priority);
        if (!icon) {
            return false;
        }

        // Create wrapper with icon and screen-reader text
        const wrapper = document.createElement('span');
        wrapper.className = 'priority-wrapper';
        wrapper.appendChild(icon);

        // Screen-reader text (hidden visually but accessible)
        const srText = document.createElement('span');
        srText.className = 'sr-only';
        srText.textContent = priority;
        wrapper.appendChild(srText);

        // Replace the text node with wrapper
        node.parentNode.replaceChild(wrapper, node);
        markProcessed(wrapper, priority);

        log('Replaced:', priority);

        return true;
    };

    /**
     * Walk DOM tree and find text nodes with priority text.
     * Uses TreeWalker for efficient DOM traversal.
     *
     * @param {Element} root - Root element to start walking from
     */
    const walkAndReplace = (root) => {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: (node) => {
                const text = node.textContent.trim();

                // Quick length check before regex (priority names are 3-9 chars)
                if (text.length < 3 || text.length > 10) {
                    return NodeFilter.FILTER_REJECT;
                }

                // Check if text matches a priority name
                if (PRIORITY_REGEX.test(text)) {
                    return NodeFilter.FILTER_ACCEPT;
                }

                return NodeFilter.FILTER_REJECT;
            },
        });

        // Collect nodes first (DOM modification during walk is problematic)
        const nodesToProcess = [];
        while (walker.nextNode()) {
            nodesToProcess.push(walker.currentNode);
        }

        // Process collected nodes
        let count = 0;
        nodesToProcess.forEach((node) => {
            if (processTextNode(node)) {
                count++;
            }
        });

        if (count > 0) {
            log(`Processed ${count} elements`);
        }
    };

    // =========================================================================
    // Main Functions
    // =========================================================================

    /**
     * Main replacement function.
     * Scans targeted containers for priority text and replaces with icons.
     */
    const replacePriorities = () => {
        log('Scanning for priority text...');

        let found = false;

        // Scan specific containers first (more efficient)
        TARGET_CONTAINERS.forEach((selector) => {
            const elements = document.querySelectorAll(selector);
            elements.forEach((el) => {
                walkAndReplace(el);
                found = true;
            });
        });

        // Fallback: scan entire body if no specific containers found
        if (!found) {
            log('No specific containers found, scanning body');
            walkAndReplace(document.body);
        }
    };

    /**
     * Debounced version of replacePriorities for MutationObserver.
     * Prevents excessive calls during rapid DOM changes.
     *
     * @type {number|null}
     */
    let debounceTimeout = null;

    /**
     * Schedule a debounced replacement scan.
     *
     * @param {number} [delay=100] - Debounce delay in milliseconds
     */
    const scheduleReplacement = (delay = 100) => {
        if (debounceTimeout) {
            clearTimeout(debounceTimeout);
        }
        debounceTimeout = setTimeout(replacePriorities, delay);
    };

    // =========================================================================
    // Event Handlers
    // =========================================================================

    /**
     * Handle PJAX navigation completion.
     * Re-scans DOM after dynamic page loads.
     */
    const handlePjaxEnd = () => {
        log('PJAX navigation detected');
        // Small delay to ensure DOM is fully updated
        scheduleReplacement(50);
    };

    /**
     * Handle DOM mutations.
     * Re-scans when new nodes are added (backup for PJAX).
     * Ignores mutations from our own icon replacements to prevent loops.
     *
     * @param {MutationRecord[]} mutations - Array of mutation records
     */
    const handleMutations = (mutations) => {
        const hasRelevantNodes = mutations.some((mutation) => {
            for (const node of mutation.addedNodes) {
                // Skip our own injected nodes
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.classList && node.classList.contains('priority-wrapper')) {
                        continue;
                    }
                }
                // Any other added node is relevant
                if (node.nodeType === Node.ELEMENT_NODE || node.nodeType === Node.TEXT_NODE) {
                    return true;
                }
            }
            return false;
        });

        if (hasRelevantNodes) {
            scheduleReplacement(100);
        }
    };

    // =========================================================================
    // Initialization
    // =========================================================================

    /**
     * Initialize the plugin.
     * Sets up event handlers and performs initial replacement.
     */
    const init = () => {
        log('Initializing...');

        // Initial replacement
        replacePriorities();

        // PJAX support - osTicket uses jQuery PJAX
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('pjax:end', handlePjaxEnd);
            log('PJAX handler registered');
        }

        // MutationObserver as backup for dynamic content
        const observer = new MutationObserver(handleMutations);
        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
        log('MutationObserver registered');

        log('Initialized successfully');
    };

    // =========================================================================
    // Entry Point
    // =========================================================================

    // Wait for DOM ready, then initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM already loaded (script loaded with defer)
        init();
    }
})(window, document);
