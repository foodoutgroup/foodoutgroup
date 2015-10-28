#set :domain,      "192.168.10.113"
set :domain,      "foodout.lt"
#set :deploy_to,   "/home/foodout/"
set :deploy_to,   "/home/sandbox.foodout.lt/"

set :scm,         :git
set :model_manager, "doctrine"

set :user, "sandbox"
set :password, "eishae8Quoneichogh2eejie0aezoh9"

set :symfony_env_prod, "prod"
ssh_options[:keys] = ["/home/foodout/.ssh/id_rsa"]

set :use_composer, true

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain                         # This may be the same as your `Web` server
role :db,         domain, :primary => true       # This is where Symfony2 migrations will run

# send notification to skype
#set :skype_topic, "skanu.lt"

# parameters file
set :parameters_file, "sandbox.yml.dist"