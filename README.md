CodeIgniter Command Line Library
================================

Command Line library makes usage of your applications in cli mode a bit simpler.

*Warning! It is a bit raw version, so beware of bugs!*


Quick start & usage
-------------------
Once installed the library, you can just start with this scratch:

1. Create your cli controller:

        <?php if ( ! defined("BASEPATH")) exit("No direct script access allowed");
            class Cli extends MY_Controller {
                public function __construct() {
                    parent::__construct();
                    if(!$this->input->is_cli_request()) {
                        show_404();
                    }
                }
            }

2. Create some sample in the controller function using Command Line library:

		public function hello() {
			// Add an argument for cli interaction
			$argumets = array(
				array('arg' => '--name',
					'alias' => '-n',
					'help' => 'the person's name, we say hello to',
					'type' => 1)
			);
			// Initialize library with arguments array
			$this->load->library("command_line", $argumets);
			// Validate input
			if (!$this->command_line->valid_input()) { 
				// If not valid, print some help
				print $this->command_line->get_help();
			} else {
				// Else do your code
				print("Hello " . $this->command_line->get_argument('-n') . "!" . PHP_EOL);
			}
		}
	
	So, lets see wat we just did there.

	At first we make an array of possible arguments sub-arrays, that our script can use. They have 4 possible keys:
 
	* **arg** - the name for the argument, for example: *--name*. **It is required if alias isn't specified.**
	* **alias** - the short alias for argument name, for example *--n*. **It is required if arg isn't specified.**
	* **help** - help string for for printing tips for argument (*the person's name*)
	* **type** - integer mask for possible types: 
		* *0* - for optional items. If you don't specify it or it's value, they will be set *FALSE*, and *TRUE* otherwise.
		* *1* - for required items. *Not specifying them with values will cause error*.
		* *2* - for boolean items. If you specify it with or without value, they will be set to *TRUE*, and *FALSE* in all other cases.
	
	Than we initialize the command_line library with the set parameters and check the input if it's valid. If not, we call for **get\_help** method, that tells what went wrong, or just make some tips for script usage. If all went ok, we are free to execute our code, accessing set parameters with **get\_argument** method, that accepts both arg name (like *--name*) or it's alias (like *-n*).



3. Now go to your index.php path in command line and enter:

		php index.php cli home
	
	You should see the help info like that:
	
	> Usage: php index.php cli hello [OPTIONS]

	> Options are:

	> -n, --name	the person's name, we say hello to

	Now enter required argument:

		php index.php cli home -n Igor

	And you will see the hello message:

	> Hello Igor!


Installation
------------
1. Download the latest version at [github](https://github.com/TrueDrago/CodeIgniter-Command-Line-Library-CCLL "Title").
2. Copy the library file to your application/libraries/ folder.
3. Use it as it described in quick start section!