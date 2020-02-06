# WP Theme Components

WP Theme Components is a simple framework for resusing code between themes.

## Installation

* Download the latest release
* Copy the `components.php` file into your theme directory.
* Include the `components.php` file in your `functions.php` file or similar.

## Installing Components

### From A Repository

* Clone the component's repository into the `theme-components` subdirectory in your theme directory.
* Alternatively, download the latest release and copy the component folder into `theme-components`.

### Creating A New Component

* Create a folder inside a `theme-components` subdirectory in your theme directory
* Inside the component folder, create a `component.php` file.
* For best results, include a docblock in the following format:
```
/**
 * Component Name
 *
 * @author Author Name
 * @version Version Number (X.Y.Z)
 * @link URL for a repository if one exists
 * @package WP_Theme_Components
 * @subpackage Name_Of_Your_Component
 */
```
