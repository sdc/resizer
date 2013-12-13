if ENV['environment'] == "production"
    set :application, "resizer"
    role :app,  "172.21.1.49"
    role :web,  "172.21.1.49"
    role :db,   "172.21.1.49", :primary => true
    set :keep_releases, 3
else
    set :application, "resizer_dev"
    role :app,  "webdev.southdevon.ac.uk"
    role :web,  "webdev.southdevon.ac.uk"
    role :db,   "webdev.southdevon.ac.uk", :primary => true
    set :keep_releases, 3
end

default_run_options[:pty] = true

set :repository,"git@github.com:sdc/resizer.git"
set :branch,    "master"
set :deploy_to, "/srv/#{application}"
set :scm, :git

namespace :deploy do
    %W(start stop restart migrate finalize_update).each do |event|
        task event do
            # don't
        end
    end
end

after "deploy:create_symlink" do
    run "chmod a+rwx #{current_path}/in"
    run "chmod a+rwx #{current_path}/out"
end

# Added to be able to remove old deployments again!
# http://stackoverflow.com/questions/17754307/cap-deploycleanup-fails-with-use-sudo-true
# $ cap customtasks:customcleanup
namespace :customtasks do
    task :customcleanup, :except => {:no_release => true} do
        count = fetch(:keep_releases, 3).to_i
        run "ls -1dt #{releases_path}/* | tail -n +#{count + 1} | #{try_sudo} xargs rm -rf"
    end
end