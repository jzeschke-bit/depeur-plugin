import React, { PureComponent } from 'react';
import ReactDOM from 'react-dom';

import '../../../../css/public/print.scss';

export default class Print extends PureComponent {
    componentDidMount() {
        this._print = document.createElement('div');
        this._print.className = `wprmprc-print${ this.props.className ? ` ${this.props.className}` : '' }`;
        this._print.innerHTML = this.props.print.current.innerHTML;

        document.body.appendChild(this._print);
        document.body.classList.add('wprmprc-printing');
        
        setTimeout(() => {
            window.print();
            setTimeout(() => {
                this.props.onFinished();
            }, 3000);
        }, 500);
    }

    componentWillUnmount() {
        ReactDOM.unmountComponentAtNode(this._print);
        document.body.removeChild(this._print);
        document.body.classList.remove('wprmprc-printing');
    }

    render() {
        return null;
    }
}
