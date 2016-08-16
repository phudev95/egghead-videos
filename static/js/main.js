var app = angular.module('egghead-videos-app', []);

app.controller('AppController', function ($scope, $http) {
    $scope.btn_submit = 'Submit';
    $scope.data = {
        rss_link: '',
        only_dload: true
    };
    $scope.url_regex = new RegExp( '(http|ftp|https)://[\\w-]+(\\.[\\w-]+)+([\\w-.,@?^=%&:/~+#-]*[\\w@?^=%&;/~+#-])?' );
    $scope.results = [];

    /**
     * Add isEmpty method to Scope
     * @param obj
     */
    $scope.isEmpty = function (obj) {
        return _.isEmpty(obj);
    };

    /**
     * Handle rss link
     */
    $scope.submit = function () {
        $scope.panel_title = '';
        $scope.btn_submit = 'Submitting...';
        $scope.results = [];

        // Request to API
        $http.post('./api/exec.php', {rss_link: $scope.data.rss_link})
            .success(function (res) {
                if (res.status && !_.isEmpty(res.data)) {
                    $scope.panel_title = res.data.title + ' (' + res.data.items.length + ' videos)';
                    $scope.results = res;
                    $scope.btn_submit = 'Submit';
                }
                else {
                    alert (res.msg || 'Error...');
                }
            });
    }
});

