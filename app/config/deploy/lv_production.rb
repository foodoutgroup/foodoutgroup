set :domain,      "d.foodout.lt"
set :deploy_to,   "/srv/vhosts/foodout.lv/"

set :scm,         :git
set :model_manager, "doctrine"

set :user, "foodoutlv"
set :password, "RjT8EThq"

set :symfony_env_prod, "prod"
ssh_options[:keys] = ["C:\Users\drawgas\.ssh\id_rsa"]

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain                         # This may be the same as your `Web` server
role :db,         domain, :primary => true       # This is where Symfony2 migrations will run

# send notification to skype
#set :skype_topic, "skanu.lt"

# parameters file
set :parameters_file, "lv_production.yml.dist"
set :kpi_file, "kpi_production_lv.yml.dist"
set :robots_file, "robots.prod.txt.dist"

after "deploy" do
    run "~/sync.sh foodout.lv"
    run "~/clearCache.sh foodout.lv"
end
