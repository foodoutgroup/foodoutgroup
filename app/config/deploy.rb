set :application, "skanu"
set :deploy_to,   "/home/labai.skanu.lt/"
set :app_path,    "app"

set :repository,  "git@github.com:Foodout/skanu.lt.git"
set :scm,         :git

set :model_manager, "doctrine"
set :branch, "master"

# multi-stage environment
set :stages,        %w(lt_beta lt_staging lt_production)
#set :stages,        %w(lv_beta lv_staging lv_production)
#~ set :stages,        %w(ee_beta ee_staging ee_production)
#~ set :stages,        %w(by_beta by_staging by_production)
# isijungiam kada reik :)
set :default_stage, "lt_production"
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

set :shared_files,      ["app/config/parameters.yml", "app/config/kpi.yml"]
set :shared_children,     ["bin", app_path + "/logs", web_path + "/uploads", web_path + "/images", "web/images", app_path + "/var", web_path + "/blog"]
set :writable_dirs,     ["bin", app_path + "/cache", app_path + "/logs", web_path + "/images", app_path + "/cache/dev", app_path + "/cache/prod", web_path + "/images/cache"]
set :composer_options, "--verbose"
# Testing purpose
# set :composer_options, "--no-dev --verbose --prefer-dist --optimize-autoloader --no-progress"

set :keep_releases, 7

namespace :deploy do
    desc "chmod things"
    task :chmod_things do
        run "chmod -R 777 #{deploy_to}current/app/cache"
        run "chmod -R 777 #{deploy_to}current/app/cache/prod"
    end
end

after "deploy", "deploy:chmod_things"
after "deploy:chmod_things", "deploy:cleanup"
# Uncomment kai bus airbrake
# after "deploy:cleanup", "deploy:airbrake_notify"
after "deploy:rollback", "symfony:cache:clear"

#
# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL
#logger.level = 0

# copy parameters.yml to specific env
set :parameters_dir, "app/config/parameters"
set :web_dir, "web"
set :parameters_file, false
set :kpi_file, false
set :robots_file, false

task :upload_parameters do
  origin_file = parameters_dir + "/" + parameters_file if parameters_dir && parameters_file
  if origin_file && File.exists?(origin_file)
    #ext = File.extname(parameters_file)
    ext = '.yml'
    relative_path = "app/config/parameters" + ext
    print "  *** relative path: "
    print relative_path
    print "\n"

    if shared_files && shared_files.include?(relative_path)
      destination_file = shared_path + "/" + relative_path
    else
      destination_file = latest_release + "/" + relative_path
    end
    try_sudo "mkdir -p #{File.dirname(destination_file)}"

    top.upload(origin_file, destination_file)
  end
end

task :upload_kpi do
    origin_file_kpi = parameters_dir + "/" + kpi_file if parameters_dir && kpi_file
    if origin_file_kpi && File.exists?(origin_file_kpi)
      #ext = File.extname(kpi_file)
      ext = '.yml'
      relative_path = "app/config/kpi" + ext
      print "  *** relative path: "
      print relative_path
      print "\n"

      if shared_files && shared_files.include?(relative_path)
        destination_file_kpi = shared_path + "/" + relative_path
      else
        destination_file_kpi = latest_release + "/" + relative_path
      end
      try_sudo "mkdir -p #{File.dirname(destination_file_kpi)}"

      top.upload(origin_file_kpi, destination_file_kpi)
    end
end

task :upload_robots do
    origin_file_robots = web_dir + "/" + robots_file if web_dir && robots_file
    if origin_file_robots && File.exists?(origin_file_robots)
      #ext = File.extname(kpi_file)
      ext = '.txt'
      relative_path = web_dir + "/robots" + ext
      print "  *** relative path: "
      print relative_path
      print "\n"

      if shared_files && shared_files.include?(relative_path)
        destination_file_robots = shared_path + "/" + relative_path
      else
        destination_file_robots = latest_release + "/" + relative_path
      end
      try_sudo "mkdir -p #{File.dirname(destination_file_robots)}"

      top.upload(origin_file_robots, destination_file_robots)
    end
end

after 'deploy:setup', 'upload_parameters', 'upload_kpi'
after 'deploy:finalize_update', 'upload_robots'
