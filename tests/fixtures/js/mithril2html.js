import Link from 'flarum/common/components/Link';
import Page from 'flarum/common/components/Page';

class HelloWorld extends Page {
    view() {
        return <p>Hello World</p>;
    }
}

class DiscussionTitle extends Page {
    oninit(vnode) {
        super.oninit(vnode);

        this.discussion = app.preloadedApiDocument();
    }

    view() {
        return <h1>{this.discussion.title()}</h1>
    }
}

class WhoAmI extends Page {
    view() {
        return m('span', app.session.user.username());
    }
}

class InternalLink extends Page {
    view() {
        return m(Link, {
            href: app.route('settings'),
        }, 'Settings');
    }
}

app.initializers.add('mithril2html-test', function () {
    app.routes.helloWorld = {
        path: '/hello-world',
        component: HelloWorld,
    };
    app.routes.discussionTitle = {
        path: '/discussion-title',
        component: DiscussionTitle,
    };
    app.routes.whoami = {
        path: '/whoami',
        component: WhoAmI,
    };
    app.routes.internalLink = {
        path: '/internal-link',
        component: InternalLink,
    };
});
