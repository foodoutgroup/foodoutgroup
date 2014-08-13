set :application, "skanu"
set :deploy_to,   "/home/labai.skanu.lt/"
set :app_path,    "app"

set :repository,  "git@github.com:Foodout/skanu.lt.git"
set :scm,         :git

set :model_manager, "doctrine"
set :branch, "the_rise_of_dark_api"

# multi-stage environment
set :stages,        %w(production staging sandbox taurinas)
# isijungiam kada reik :)
set :default_stage, "taurinas"
set :stage_dir,     "app/config/deploy"
require 'capistrano/ext/multistage'

#role :web,        domain                         # Your HTTP server, Apache/etc
#role :app,        domain, :primary => true       # This may be the same as your `Web` server

set :deploy_via, :remote_cache

set :use_composer, true
# share vendors files
set :copy_vendors, true
#set :update_vendors, true

# man atrodo šito nereikia, nes jis skirtas tik tiems atvejams, kai nenaudojamas composer™
# set :update_vendors, true

set :use_sudo, false
set :dump_assetic_assets, true

ssh_options[:keys] = ["C:\Users\drawgas\.ssh\id_rsa"]
ssh_options[:forward_agent] = true
ssh_options[:port] = 22
default_run_options[:pty] = true

set :shared_files,      ["app/config/parameters.yml"]
set :shared_children,     ["bin", app_path + "/logs", web_path + "/uploads", web_path + "/images", "web/images", app_path + "/var"]
set :writable_dirs,     ["bin", app_path + "/cache", app_path + "/logs", web_path + "/images", app_path + "/cache/dev", app_path + "/cache/prod", web_path + "/images/cache"]
set :composer_options, "--verbose"
# Testing purpose
# set :composer_options, "--no-dev --verbose --prefer-dist --optimize-autoloader --no-progress"

# set :keep_releases, 5

namespace :deploy do
    desc "chmod things"
    task :chmod_things do
        run "chmod -R 777 #{deploy_to}current/app/cache"
        run "chmod -R 777 #{deploy_to}current/app/cache/prod"
    end
end

after "deploy", "deploy:cleanup"
after "deploy", "deploy:chmod_things"
# Uncomment kai bus airbrake
# after "deploy:cleanup", "deploy:airbrake_notify"
after "deploy:rollback", "symfony:cache:clear"

# Be more verbose by uncommenting the following line
# logger.level = Logger::MAX_LEVEL
logger.level = 0

# copy parameters.yml to specific env
set :parameters_dir, "app/config/parameters"
set :parameters_file, false

task :upload_parameters do
  origin_file = parameters_dir + "/" + parameters_file if parameters_dir && parameters_file
  if origin_file && File.exists?(origin_file)
    #ext = File.extname(parameters_file)
    ext = '.yml'
    relative_path = "app/config/parameters" + ext

    if shared_files && shared_files.include?(relative_path)
      destination_file = shared_path + "/" + relative_path
    else
      destination_file = latest_release + "/" + relative_path
    end
    try_sudo "mkdir -p #{File.dirname(destination_file)}"

    top.upload(origin_file, destination_file)
  end
end

after 'deploy:setup', 'upload_parameters'