var app = angular.module('egghead-videos-app', []);

app.controller('AppController', function ($scope, $http) {
    $scope.btn_submit = 'Handle';
    $scope.data = {
        rss_link: 'https://egghead.io/courses/practical-git-for-everyday-professional-use/course_feed?user_email=phudev95%40gmail.com&user_token=61d065de-76e8-4c3a-aa81-6eaf26068de4'
    };
    $scope.url_regex = new RegExp( '(http|ftp|https)://[\\w-]+(\\.[\\w-]+)+([\\w-.,@?^=%&:/~+#-]*[\\w@?^=%&;/~+#-])?' );
    $scope.results = [];

    $scope.parse_title = function (str) {
        str = str || '';
        var data = {title: '', category: ''};
        var matches = str.match(/^(\w+)\s*-\s*(.+)$/);

        if (matches.length === 3) {
            data.title = matches[2];
            data.category = matches[1];
        }

        return data;
    };

    /**
     * Handle rss link
     */
    $scope.handle = function () {
        $scope.btn_submit = 'Handling...';
        var temp = {};
        var parse_title = '';

        // Request to API
        $http.post('./api/get_videos.php', {rss_link: $scope.data.rss_link})
            .success(function (res) {
                var json = JSON.parse(res.data);
                console.warn(json);
                if (json && json.channel && !_.isEmpty(json.channel.item)) {
                    var items = json.channel.item;
                    _.each(items, function (item, i) {
                        parse_title = $scope.parse_title(item.title);
                        temp = {
                            title: parse_title.title,
                            category: parse_title.category,
                            duration: '',
                            author: '',
                            length: '',
                            source: ''
                        };
                        if (i == 0) console.log(temp);
                    });
                }
            });

    }
});

