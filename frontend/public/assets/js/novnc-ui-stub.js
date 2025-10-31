// Minimal stub for noVNC core/input/keyboard.js dependency on '../../app/ui.js'
// We only expose the properties that keyboard.js touches.
export default {
  rfb: {
    // When falsey, keyboard.js will skip Mac CMD->CTRL shortcut translation
    translateShortcuts: false,
  },
};
