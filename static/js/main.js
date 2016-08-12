var app = angular.module('egghead-videos-app', []);

app.controller('AppController', function ($scope, $http) {
    $scope.btn_submit = 'Submit';
    $scope.data = {
        rss_link: 'https://egghead.io/courses/practical-git-for-everyday-professional-use/course_feed?user_email=phudev95%40gmail.com&user_token=61d065de-76e8-4c3a-aa81-6eaf26068de4'
    };
    $scope.url_regex = new RegExp( '(http|ftp|https)://[\\w-]+(\\.[\\w-]+)+([\\w-.,@?^=%&:/~+#-]*[\\w@?^=%&;/~+#-])?' );
    $scope.results = [];

    /**
     * Handle rss link
     */
    $scope.submit = function () {
        $scope.btn_submit = 'Submitting...';
        $scope.results = [];

        // Request to API
        $http.post('./api/get_videos.php', {rss_link: $scope.data.rss_link})
            .success(function (res) {
                if (res.status && !_.isEmpty(res.data)) {
                    $scope.results = res;
                    $scope.btn_submit = 'Submit';
                }
                else {
                    alert (res.msg || 'Error...');
                }
            });
    }
});

