set :domain,      "d.foodout.lt"
set :deploy_to,   "/srv/vhosts/staging.mychef.ee/"

set :scm,         :git
set :model_manager, "doctrine"

set :user, "foodoutlt"
set :password, "aZaNFU6b"

set :symfony_env_prod, "prod"
ssh_options[:keys] = ["C:\Users\drawgas\.ssh\id_rsa"]

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain                         # This may be the same as your `Web` server
role :db,         domain, :primary => true       # This is where Symfony2 migrations will run

# send notification to skype
#set :skype_topic, "skanu.lt"

# parameters file
set :parameters_file, "ee_staging.yml.dist"
set :robots_file, "robots.dev.txt.dist"

after "deploy" do
    run "~/sync.sh staging.mychef.ee"
end
